<?php

class Turma extends BaseModel
{
    public function all($somenteArquivadas = false)
    {
        $sql = 'SELECT turmas.id, turmas.codigo, turmas.titulo, turmas.arquivada, turmas.created_at,
                       (SELECT COUNT(*) FROM usuarios
                        WHERE usuarios.turma_id = turmas.id
                          AND usuarios.perfil = \'aluno\'
                          AND usuarios.status = \'aprovado\') AS alunos_count
                FROM turmas
                WHERE turmas.arquivada = :arquivada
                ORDER BY turmas.codigo ASC';

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':arquivada', $somenteArquivadas ? 1 : 0, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
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

    public function delete($id)
    {
        $statement = $this->connection->prepare('DELETE FROM turmas WHERE id = :id');
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function setArquivada($id, $arquivada)
    {
        $statement = $this->connection->prepare(
            'UPDATE turmas SET arquivada = :arquivada WHERE id = :id'
        );
        $statement->bindValue(':arquivada', $arquivada ? 1 : 0, PDO::PARAM_INT);
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);

        return $statement->execute();
    }

    public function countAll()
    {
        $row = $this->connection->query(
            'SELECT COUNT(*) AS total FROM turmas WHERE arquivada = 0'
        )->fetch();

        return $row ? (int) $row['total'] : 0;
    }
}
