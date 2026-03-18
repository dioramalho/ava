<?php

class Turma extends BaseModel
{
    public function all()
    {
        $statement = $this->connection->query('SELECT id, nome, ano_letivo, turno, descricao, created_at FROM turmas ORDER BY ano_letivo DESC, nome ASC');
        return $statement->fetchAll();
    }

    public function create($data)
    {
        $statement = $this->connection->prepare('INSERT INTO turmas (nome, ano_letivo, turno, descricao) VALUES (:nome, :ano_letivo, :turno, :descricao)');
        $statement->execute(array(
            ':nome' => $data['nome'],
            ':ano_letivo' => $data['ano_letivo'],
            ':turno' => $data['turno'],
            ':descricao' => $data['descricao']
        ));

        return $this->connection->lastInsertId();
    }
}
