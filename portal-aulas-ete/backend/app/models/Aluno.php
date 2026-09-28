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

    public function findById($id)
    {
        $statement = $this->connection->prepare('SELECT id, turma_id, nome, email, matricula FROM alunos WHERE id = :id LIMIT 1');
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
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

    public function update($id, $data)
    {
        $statement = $this->connection->prepare('UPDATE alunos SET turma_id = :turma_id, nome = :nome, email = :email, matricula = :matricula WHERE id = :id');
        $statement->execute(array(
            ':id' => (int) $id,
            ':turma_id' => $data['turma_id'],
            ':nome' => $data['nome'],
            ':email' => $data['email'],
            ':matricula' => $data['matricula']
        ));

        return $statement->rowCount() > 0;
    }

    public function delete($id)
    {
        $statement = $this->connection->prepare('DELETE FROM alunos WHERE id = :id');
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }
}
