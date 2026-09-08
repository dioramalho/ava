<?php

class Disciplina extends BaseModel
{
    public function all()
    {
        $statement = $this->connection->query(
            'SELECT id, codigo, titulo, created_at FROM disciplinas ORDER BY codigo ASC'
        );

        return $statement->fetchAll();
    }

    public function findById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT id, codigo, titulo FROM disciplinas WHERE id = :id LIMIT 1'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
    }

    public function findByCodigo($codigo, $exceptId)
    {
        $sql = 'SELECT id FROM disciplinas WHERE codigo = :codigo';
        $params = array(':codigo' => $codigo);

        if ($exceptId) {
            $sql .= ' AND id <> :id';
            $params[':id'] = (int) $exceptId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetch();
    }

    public function create($data)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO disciplinas (codigo, titulo) VALUES (:codigo, :titulo)'
        );
        $statement->execute(array(
            ':codigo' => $data['codigo'],
            ':titulo' => $data['titulo']
        ));

        return $this->connection->lastInsertId();
    }

    public function countAll()
    {
        $row = $this->connection->query('SELECT COUNT(*) AS total FROM disciplinas')->fetch();

        return $row ? (int) $row['total'] : 0;
    }
}
