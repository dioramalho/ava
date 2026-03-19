<?php

class Aluno extends BaseModel
{
    public function all()
    {
        $sql = 'SELECT alunos.id, alunos.nome, alunos.email, alunos.matricula, alunos.created_at, turmas.id AS turma_id, turmas.nome AS turma_nome
                FROM alunos
                INNER JOIN turmas ON turmas.id = alunos.turma_id
                ORDER BY alunos.nome ASC';

        return $this->connection->query($sql)->fetchAll();
    }

    public function create($data)
    {
        $statement = $this->connection->prepare('INSERT INTO alunos (turma_id, nome, email, matricula) VALUES (:turma_id, :nome, :email, :matricula)');
        $statement->execute(array(
            ':turma_id' => $data['turma_id'],
            ':nome' => $data['nome'],
            ':email' => $data['email'],
            ':matricula' => $data['matricula']
        ));

        return $this->connection->lastInsertId();
    }
}
