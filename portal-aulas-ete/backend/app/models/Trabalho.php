<?php

class Trabalho extends BaseModel
{
    public function all()
    {
        $sql = 'SELECT trabalhos.id, trabalhos.titulo, trabalhos.data_entrega, trabalhos.created_at,
                       turmas.id AS turma_id, turmas.codigo AS turma_codigo, turmas.titulo AS turma_titulo,
                       aulas.id AS aula_id, aulas.titulo AS aula_titulo,
                       (SELECT COUNT(*) FROM trabalho_alunos WHERE trabalho_alunos.trabalho_id = trabalhos.id) AS alunos_count,
                       (SELECT COUNT(*) FROM trabalho_alunos WHERE trabalho_alunos.trabalho_id = trabalhos.id AND trabalho_alunos.status = \'pendente\') AS pendentes_count
                FROM trabalhos
                INNER JOIN turmas ON turmas.id = trabalhos.turma_id
                INNER JOIN aulas ON aulas.id = trabalhos.aula_id
                ORDER BY trabalhos.created_at DESC, trabalhos.id DESC';

        return $this->connection->query($sql)->fetchAll();
    }

    public function findById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT trabalhos.id, trabalhos.titulo, trabalhos.descricao, trabalhos.data_entrega,
                    trabalhos.turma_id, trabalhos.aula_id, trabalhos.arquivo_nome, trabalhos.arquivo_caminho,
                    trabalhos.arquivo_extensao, trabalhos.video_url, trabalhos.created_at,
                    turmas.codigo AS turma_codigo, turmas.titulo AS turma_titulo,
                    aulas.titulo AS aula_titulo, disciplinas.titulo AS disciplina_titulo
             FROM trabalhos
             INNER JOIN turmas ON turmas.id = trabalhos.turma_id
             INNER JOIN aulas ON aulas.id = trabalhos.aula_id
             INNER JOIN disciplinas ON disciplinas.id = aulas.disciplina_id
             WHERE trabalhos.id = :id
             LIMIT 1'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        $trabalho = $statement->fetch();

        return $trabalho ? $trabalho : null;
    }

    public function create($data)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO trabalhos
                (titulo, descricao, data_entrega, turma_id, aula_id, arquivo_nome, arquivo_caminho, arquivo_extensao, video_url)
             VALUES
                (:titulo, :descricao, :data_entrega, :turma_id, :aula_id, :arquivo_nome, :arquivo_caminho, :arquivo_extensao, :video_url)'
        );
        $statement->execute(array(
            ':titulo' => $data['titulo'],
            ':descricao' => $data['descricao'],
            ':data_entrega' => $data['data_entrega'],
            ':turma_id' => (int) $data['turma_id'],
            ':aula_id' => (int) $data['aula_id'],
            ':arquivo_nome' => $data['arquivo_nome'],
            ':arquivo_caminho' => $data['arquivo_caminho'],
            ':arquivo_extensao' => $data['arquivo_extensao'],
            ':video_url' => $data['video_url']
        ));

        return (int) $this->connection->lastInsertId();
    }

    public function alunosAprovadosDaTurma($turmaId)
    {
        $statement = $this->connection->prepare(
            'SELECT id, nome, email
             FROM usuarios
             WHERE perfil = :perfil AND status = :status AND turma_id = :turma_id
             ORDER BY nome ASC'
        );
        $statement->execute(array(
            ':perfil' => 'aluno',
            ':status' => 'aprovado',
            ':turma_id' => (int) $turmaId
        ));

        return $statement->fetchAll();
    }

    public function findAlunoAprovado($alunoId, $turmaId)
    {
        $statement = $this->connection->prepare(
            'SELECT id, nome, email
             FROM usuarios
             WHERE id = :id AND perfil = :perfil AND status = :status AND turma_id = :turma_id
             LIMIT 1'
        );
        $statement->execute(array(
            ':id' => (int) $alunoId,
            ':perfil' => 'aluno',
            ':status' => 'aprovado',
            ':turma_id' => (int) $turmaId
        ));
        $aluno = $statement->fetch();

        return $aluno ? $aluno : null;
    }

    public function sincronizarTrabalho($trabalho)
    {
        $alunos = $this->alunosAprovadosDaTurma($trabalho['turma_id']);
        $novos = array();

        foreach ($alunos as $aluno) {
            if ($this->findParticipacaoPorAluno($trabalho['id'], $aluno['id'])) {
                continue;
            }

            $participacaoId = $this->criarParticipacao($trabalho, $aluno['id']);
            $this->addEvento($participacaoId, array(
                'tipo' => 'publicado',
                'autor_id' => null,
                'autor_perfil' => 'sistema',
                'texto' => 'Trabalho disponível para entrega.',
                'arquivo_nome' => null,
                'arquivo_caminho' => null,
                'arquivo_extensao' => null,
                'data_entrega' => $trabalho['data_entrega'],
                'status' => 'pendente'
            ));
            $aluno['participacao_id'] = $participacaoId;
            $novos[] = $aluno;
        }

        return $novos;
    }

    public function sincronizarAluno($turmaId, $alunoId)
    {
        $aluno = $this->findAlunoAprovado($alunoId, $turmaId);
        if (!$aluno) {
            return;
        }

        $statement = $this->connection->prepare(
            'SELECT id, data_entrega FROM trabalhos WHERE turma_id = :turma_id'
        );
        $statement->bindValue(':turma_id', (int) $turmaId, PDO::PARAM_INT);
        $statement->execute();
        $trabalhos = $statement->fetchAll();

        foreach ($trabalhos as $trabalho) {
            if ($this->findParticipacaoPorAluno($trabalho['id'], $alunoId)) {
                continue;
            }

            $participacaoId = $this->criarParticipacao($trabalho, $alunoId);
            $this->addEvento($participacaoId, array(
                'tipo' => 'publicado',
                'autor_id' => null,
                'autor_perfil' => 'sistema',
                'texto' => 'Trabalho disponível para entrega.',
                'arquivo_nome' => null,
                'arquivo_caminho' => null,
                'arquivo_extensao' => null,
                'data_entrega' => $trabalho['data_entrega'],
                'status' => 'pendente'
            ));
        }
    }

    public function participacoesDoTrabalho($trabalhoId)
    {
        $statement = $this->connection->prepare(
            'SELECT trabalho_alunos.id, trabalho_alunos.trabalho_id, trabalho_alunos.aluno_id,
                    trabalho_alunos.status, trabalho_alunos.data_entrega_vigente,
                    usuarios.nome AS aluno_nome, usuarios.email AS aluno_email
             FROM trabalho_alunos
             INNER JOIN usuarios ON usuarios.id = trabalho_alunos.aluno_id
             WHERE trabalho_alunos.trabalho_id = :trabalho_id
             ORDER BY usuarios.nome ASC'
        );
        $statement->bindValue(':trabalho_id', (int) $trabalhoId, PDO::PARAM_INT);
        $statement->execute();
        $linhas = $statement->fetchAll();

        foreach ($linhas as $indice => $linha) {
            $linhas[$indice]['eventos'] = $this->eventosDaParticipacao($linha['id']);
            $linhas[$indice]['ultima_entrega'] = $this->ultimaEntrega($linhas[$indice]['eventos']);
        }

        return $linhas;
    }

    public function findParticipacao($id)
    {
        $statement = $this->connection->prepare(
            'SELECT trabalho_alunos.id, trabalho_alunos.trabalho_id, trabalho_alunos.aluno_id,
                    trabalho_alunos.status, trabalho_alunos.data_entrega_vigente,
                    usuarios.nome AS aluno_nome, usuarios.email AS aluno_email
             FROM trabalho_alunos
             INNER JOIN usuarios ON usuarios.id = trabalho_alunos.aluno_id
             WHERE trabalho_alunos.id = :id
             LIMIT 1'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();
        $linha = $statement->fetch();

        return $linha ? $linha : null;
    }

    public function atualizarStatus($id, $status)
    {
        $statement = $this->connection->prepare(
            'UPDATE trabalho_alunos
             SET status = :status, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $statement->execute(array(
            ':status' => $status,
            ':id' => (int) $id
        ));
    }

    public function reabrirEntrega($id, $dataEntrega)
    {
        $statement = $this->connection->prepare(
            'UPDATE trabalho_alunos
             SET status = :status, data_entrega_vigente = :data_entrega, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $statement->execute(array(
            ':status' => 'pendente',
            ':data_entrega' => $dataEntrega,
            ':id' => (int) $id
        ));
    }

    public function addEvento($participacaoId, $data)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO trabalho_eventos
                (trabalho_aluno_id, tipo, autor_id, autor_perfil, texto, arquivo_nome, arquivo_caminho, arquivo_extensao, data_entrega, status)
             VALUES
                (:trabalho_aluno_id, :tipo, :autor_id, :autor_perfil, :texto, :arquivo_nome, :arquivo_caminho, :arquivo_extensao, :data_entrega, :status)'
        );
        $statement->execute(array(
            ':trabalho_aluno_id' => (int) $participacaoId,
            ':tipo' => $data['tipo'],
            ':autor_id' => $data['autor_id'],
            ':autor_perfil' => $data['autor_perfil'],
            ':texto' => $data['texto'],
            ':arquivo_nome' => $data['arquivo_nome'],
            ':arquivo_caminho' => $data['arquivo_caminho'],
            ':arquivo_extensao' => $data['arquivo_extensao'],
            ':data_entrega' => $data['data_entrega'],
            ':status' => $data['status']
        ));

        return (int) $this->connection->lastInsertId();
    }

    public function eventosDaParticipacao($participacaoId)
    {
        $statement = $this->connection->prepare(
            'SELECT trabalho_eventos.id, trabalho_eventos.tipo, trabalho_eventos.autor_id, trabalho_eventos.autor_perfil,
                    trabalho_eventos.texto, trabalho_eventos.arquivo_nome, trabalho_eventos.arquivo_caminho,
                    trabalho_eventos.arquivo_extensao, trabalho_eventos.data_entrega, trabalho_eventos.status,
                    trabalho_eventos.created_at, usuarios.nome AS autor_nome
             FROM trabalho_eventos
             LEFT JOIN usuarios ON usuarios.id = trabalho_eventos.autor_id
             WHERE trabalho_eventos.trabalho_aluno_id = :id
             ORDER BY trabalho_eventos.created_at ASC, trabalho_eventos.id ASC'
        );
        $statement->bindValue(':id', (int) $participacaoId, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    public function listarDoAluno($alunoId, $turmaId)
    {
        $statement = $this->connection->prepare(
            'SELECT trabalhos.id, trabalhos.titulo, trabalhos.descricao, trabalhos.data_entrega,
                    trabalhos.arquivo_nome, trabalhos.video_url,
                    trabalho_alunos.id AS participacao_id, trabalho_alunos.status, trabalho_alunos.data_entrega_vigente,
                    aulas.titulo AS aula_titulo, disciplinas.titulo AS disciplina_titulo
             FROM trabalhos
             INNER JOIN trabalho_alunos ON trabalho_alunos.trabalho_id = trabalhos.id AND trabalho_alunos.aluno_id = :aluno_id
             INNER JOIN aulas ON aulas.id = trabalhos.aula_id
             INNER JOIN disciplinas ON disciplinas.id = aulas.disciplina_id
             WHERE trabalhos.turma_id = :turma_id
             ORDER BY CASE WHEN trabalho_alunos.status = \'pendente\' THEN 0 ELSE 1 END,
                      trabalho_alunos.data_entrega_vigente ASC,
                      trabalhos.id DESC'
        );
        $statement->execute(array(
            ':aluno_id' => (int) $alunoId,
            ':turma_id' => (int) $turmaId
        ));

        return $statement->fetchAll();
    }

    public function pendentesDoAluno($alunoId, $turmaId)
    {
        $lista = $this->listarDoAluno($alunoId, $turmaId);
        $pendentes = array();

        foreach ($lista as $item) {
            if ($item['status'] === 'pendente') {
                $pendentes[] = $item;
            }
        }

        return $pendentes;
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

    private function findParticipacaoPorAluno($trabalhoId, $alunoId)
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM trabalho_alunos WHERE trabalho_id = :trabalho_id AND aluno_id = :aluno_id LIMIT 1'
        );
        $statement->execute(array(
            ':trabalho_id' => (int) $trabalhoId,
            ':aluno_id' => (int) $alunoId
        ));
        $linha = $statement->fetch();

        return $linha ? $linha : null;
    }

    private function criarParticipacao($trabalho, $alunoId)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO trabalho_alunos (trabalho_id, aluno_id, status, data_entrega_vigente)
             VALUES (:trabalho_id, :aluno_id, :status, :data_entrega_vigente)'
        );
        $statement->execute(array(
            ':trabalho_id' => (int) $trabalho['id'],
            ':aluno_id' => (int) $alunoId,
            ':status' => 'pendente',
            ':data_entrega_vigente' => $trabalho['data_entrega']
        ));

        return (int) $this->connection->lastInsertId();
    }

    private function ultimaEntrega($eventos)
    {
        $ultima = null;

        foreach ($eventos as $evento) {
            if ($evento['tipo'] === 'entrega') {
                $ultima = $evento;
            }
        }

        return $ultima;
    }
}
