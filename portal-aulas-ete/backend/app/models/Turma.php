<?php

class Turma extends BaseModel
{
    public function all()
    {
        $sql = 'SELECT turmas.id, turmas.codigo, turmas.titulo, turmas.created_at,
                       (SELECT COUNT(*) FROM usuarios
                        WHERE usuarios.turma_id = turmas.id
                          AND usuarios.perfil = \'aluno\'
                          AND usuarios.status = \'aprovado\') AS alunos_count
                FROM turmas
                ORDER BY turmas.codigo ASC';

        return $this->connection->query($sql)->fetchAll();
    }

    public function findById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT id, codigo, titulo FROM turmas WHERE id = :id LIMIT 1'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
    }

    public function findByCodigo($codigo, $exceptId)
    {
        $sql = 'SELECT id FROM turmas WHERE codigo = :codigo';
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
            'INSERT INTO turmas (codigo, titulo) VALUES (:codigo, :titulo)'
        );
        $statement->execute(array(
            ':codigo' => $data['codigo'],
            ':titulo' => $data['titulo']
        ));

        return $this->connection->lastInsertId();
    }

    public function update($id, $data)
    {
        $statement = $this->connection->prepare(
            'UPDATE turmas SET codigo = :codigo, titulo = :titulo WHERE id = :id'
        );

        return $statement->execute(array(
            ':codigo' => $data['codigo'],
            ':titulo' => $data['titulo'],
            ':id' => (int) $id
        ));
    }

    public function countAll()
    {
        $row = $this->connection->query('SELECT COUNT(*) AS total FROM turmas')->fetch();

        return $row ? (int) $row['total'] : 0;
    }
}
