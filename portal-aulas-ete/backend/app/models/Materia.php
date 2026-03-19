<?php

class Materia extends BaseModel
{
    public function all($turmaId)
    {
        $sql = 'SELECT materias.id, materias.nome, materias.descricao, materias.created_at, turmas.id AS turma_id, turmas.nome AS turma_nome
                FROM materias
                INNER JOIN turmas ON turmas.id = materias.turma_id';

        if ($turmaId) {
            $sql .= ' WHERE materias.turma_id = :turma_id';
        }

        $sql .= ' ORDER BY materias.nome ASC';

        $statement = $this->connection->prepare($sql);

        if ($turmaId) {
            $statement->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
        }

        $statement->execute();
        return $statement->fetchAll();
    }

    public function create($data)
    {
        $statement = $this->connection->prepare('INSERT INTO materias (turma_id, nome, descricao) VALUES (:turma_id, :nome, :descricao)');
        $statement->execute(array(
            ':turma_id' => $data['turma_id'],
            ':nome' => $data['nome'],
            ':descricao' => $data['descricao']
        ));

        return $this->connection->lastInsertId();
    }
}
