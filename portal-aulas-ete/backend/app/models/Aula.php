<?php

class Aula extends BaseModel
{
    public function all($turmaId, $disciplinaId)
    {
        $sql = 'SELECT aulas.id, aulas.titulo, aulas.created_at,
                       turmas.id AS turma_id, turmas.codigo AS turma_codigo, turmas.titulo AS turma_titulo,
                       disciplinas.id AS disciplina_id, disciplinas.codigo AS disciplina_codigo, disciplinas.titulo AS disciplina_titulo,
                       (SELECT COUNT(*) FROM aula_videos WHERE aula_videos.aula_id = aulas.id) AS videos_count,
                       (SELECT COUNT(*) FROM aula_arquivos WHERE aula_arquivos.aula_id = aulas.id) AS arquivos_count
                FROM aulas
                INNER JOIN turmas ON turmas.id = aulas.turma_id
                INNER JOIN disciplinas ON disciplinas.id = aulas.disciplina_id
                WHERE 1 = 1';

        $params = array();

        if ($turmaId > 0) {
            $sql .= ' AND aulas.turma_id = :turma_id';
            $params[':turma_id'] = $turmaId;
        }

        if ($disciplinaId > 0) {
            $sql .= ' AND aulas.disciplina_id = :disciplina_id';
            $params[':disciplina_id'] = $disciplinaId;
        }

        $sql .= ' ORDER BY aulas.created_at DESC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function disciplinasDaTurma($turmaId)
    {
        if ((int) $turmaId <= 0) {
            return array();
        }

        $sql = 'SELECT disciplinas.id, disciplinas.codigo, disciplinas.titulo,
                       COUNT(aulas.id) AS aulas_count
                FROM aulas
                INNER JOIN disciplinas ON disciplinas.id = aulas.disciplina_id
                WHERE aulas.turma_id = :turma_id
                GROUP BY disciplinas.id, disciplinas.codigo, disciplinas.titulo
                ORDER BY disciplinas.codigo ASC';
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':turma_id', (int) $turmaId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function recentByTurma($turmaId, $limit)
    {
        if ((int) $turmaId <= 0) {
            return array();
        }

        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 5;
        }

        $sql = 'SELECT aulas.id, aulas.titulo, aulas.created_at,
                       turmas.codigo AS turma_codigo,
                       disciplinas.id AS disciplina_id,
                       disciplinas.titulo AS disciplina_titulo,
                       (SELECT COUNT(*) FROM aula_videos WHERE aula_videos.aula_id = aulas.id) AS videos_count,
                       (SELECT COUNT(*) FROM aula_arquivos WHERE aula_arquivos.aula_id = aulas.id) AS arquivos_count
                FROM aulas
                INNER JOIN turmas ON turmas.id = aulas.turma_id
                INNER JOIN disciplinas ON disciplinas.id = aulas.disciplina_id
                WHERE aulas.turma_id = :turma_id
                ORDER BY aulas.created_at DESC
                LIMIT ' . $limit;
        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':turma_id', (int) $turmaId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function recent($limit)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            $limit = 5;
        }

        $sql = 'SELECT aulas.id, aulas.titulo, aulas.created_at,
                       turmas.codigo AS turma_codigo,
                       disciplinas.titulo AS disciplina_titulo
                FROM aulas
                INNER JOIN turmas ON turmas.id = aulas.turma_id
                INNER JOIN disciplinas ON disciplinas.id = aulas.disciplina_id
                ORDER BY aulas.created_at DESC
                LIMIT ' . $limit;

        return $this->connection->query($sql)->fetchAll();
    }

    public function findById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT aulas.id, aulas.titulo, aulas.turma_id, aulas.disciplina_id, aulas.created_at,
                    turmas.codigo AS turma_codigo, turmas.titulo AS turma_titulo,
                    disciplinas.codigo AS disciplina_codigo, disciplinas.titulo AS disciplina_titulo
             FROM aulas
             INNER JOIN turmas ON turmas.id = aulas.turma_id
             INNER JOIN disciplinas ON disciplinas.id = aulas.disciplina_id
             WHERE aulas.id = :id
             LIMIT 1'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        $aula = $statement->fetch();
        if (!$aula) {
            return null;
        }

        $aula['videos'] = $this->videosByAula((int) $id);
        $aula['arquivos'] = $this->arquivosByAula((int) $id);

        return $aula;
    }

    public function videosByAula($aulaId)
    {
        $statement = $this->connection->prepare(
            'SELECT id, url, ordem FROM aula_videos WHERE aula_id = :aula_id ORDER BY ordem ASC, id ASC'
        );
        $statement->bindValue(':aula_id', (int) $aulaId, PDO::PARAM_INT);
        $statement->execute();

        $videos = $statement->fetchAll();
        for ($i = 0; $i < count($videos); $i++) {
            $videos[$i]['embed_url'] = LessonUpload::youtubeEmbedUrl($videos[$i]['url']);
        }

        return $videos;
    }

    public function arquivosByAula($aulaId)
    {
        $statement = $this->connection->prepare(
            'SELECT id, nome_original, caminho_arquivo, extensao FROM aula_arquivos WHERE aula_id = :aula_id ORDER BY id ASC'
        );
        $statement->bindValue(':aula_id', (int) $aulaId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function create($data)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO aulas (titulo, turma_id, disciplina_id) VALUES (:titulo, :turma_id, :disciplina_id)'
        );
        $statement->execute(array(
            ':titulo' => $data['titulo'],
            ':turma_id' => $data['turma_id'],
            ':disciplina_id' => $data['disciplina_id']
        ));

        return $this->connection->lastInsertId();
    }

    public function update($id, $data)
    {
        $statement = $this->connection->prepare(
            'UPDATE aulas SET titulo = :titulo, turma_id = :turma_id, disciplina_id = :disciplina_id WHERE id = :id'
        );

        return $statement->execute(array(
            ':titulo' => $data['titulo'],
            ':turma_id' => $data['turma_id'],
            ':disciplina_id' => $data['disciplina_id'],
            ':id' => (int) $id
        ));
    }

    public function addVideo($aulaId, $url, $ordem)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO aula_videos (aula_id, url, ordem) VALUES (:aula_id, :url, :ordem)'
        );

        return $statement->execute(array(
            ':aula_id' => (int) $aulaId,
            ':url' => $url,
            ':ordem' => (int) $ordem
        ));
    }

    public function deleteVideos($aulaId)
    {
        $statement = $this->connection->prepare('DELETE FROM aula_videos WHERE aula_id = :aula_id');
        $statement->bindValue(':aula_id', (int) $aulaId, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function addArquivo($data)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO aula_arquivos (aula_id, nome_original, caminho_arquivo, extensao)
             VALUES (:aula_id, :nome_original, :caminho_arquivo, :extensao)'
        );

        return $statement->execute(array(
            ':aula_id' => $data['aula_id'],
            ':nome_original' => $data['nome_original'],
            ':caminho_arquivo' => $data['caminho_arquivo'],
            ':extensao' => $data['extensao']
        ));
    }

    public function countAll()
    {
        $row = $this->connection->query('SELECT COUNT(*) AS total FROM aulas')->fetch();

        return $row ? (int) $row['total'] : 0;
    }

    public function beginTransaction()
    {
        return $this->connection->beginTransaction();
    }

    public function commit()
    {
        return $this->connection->commit();
    }

    public function rollBack()
    {
        if ($this->connection->inTransaction()) {
            return $this->connection->rollBack();
        }

        return false;
    }
}
