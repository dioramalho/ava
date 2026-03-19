<?php

class Documento extends BaseModel
{
    public function all($turmaId)
    {
        $sql = 'SELECT documentos.id, documentos.titulo, documentos.nome_arquivo, documentos.caminho_arquivo, documentos.descricao, documentos.data_publicacao,
                       turmas.id AS turma_id, turmas.nome AS turma_nome,
                       materias.id AS materia_id, materias.nome AS materia_nome
                FROM documentos
                INNER JOIN turmas ON turmas.id = documentos.turma_id
                INNER JOIN materias ON materias.id = documentos.materia_id';

        if ($turmaId) {
            $sql .= ' WHERE documentos.turma_id = :turma_id';
        }

        $sql .= ' ORDER BY documentos.data_publicacao DESC';

        $statement = $this->connection->prepare($sql);

        if ($turmaId) {
            $statement->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
        }

        $statement->execute();
        return $statement->fetchAll();
    }

    public function create($data)
    {
        $statement = $this->connection->prepare('INSERT INTO documentos (turma_id, materia_id, titulo, nome_arquivo, caminho_arquivo, descricao, data_publicacao) VALUES (:turma_id, :materia_id, :titulo, :nome_arquivo, :caminho_arquivo, :descricao, CURRENT_TIMESTAMP)');
        $statement->execute(array(
            ':turma_id' => $data['turma_id'],
            ':materia_id' => $data['materia_id'],
            ':titulo' => $data['titulo'],
            ':nome_arquivo' => $data['nome_arquivo'],
            ':caminho_arquivo' => $data['caminho_arquivo'],
            ':descricao' => $data['descricao']
        ));

        return $this->connection->lastInsertId();
    }
}
