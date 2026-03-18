<?php

class Recado extends BaseModel
{
    public function all($turmaId)
    {
        $sql = 'SELECT recados.id, recados.titulo, recados.mensagem, recados.data_publicacao, turmas.id AS turma_id, turmas.nome AS turma_nome
                FROM recados
                INNER JOIN turmas ON turmas.id = recados.turma_id';

        if ($turmaId) {
            $sql .= ' WHERE recados.turma_id = :turma_id';
        }

        $sql .= ' ORDER BY recados.data_publicacao DESC';

        $statement = $this->connection->prepare($sql);

        if ($turmaId) {
            $statement->bindValue(':turma_id', $turmaId, PDO::PARAM_INT);
        }

        $statement->execute();
        return $statement->fetchAll();
    }

    public function create($data)
    {
        $statement = $this->connection->prepare('INSERT INTO recados (turma_id, titulo, mensagem, data_publicacao) VALUES (:turma_id, :titulo, :mensagem, :data_publicacao)');
        $statement->execute(array(
            ':turma_id' => $data['turma_id'],
            ':titulo' => $data['titulo'],
            ':mensagem' => $data['mensagem'],
            ':data_publicacao' => date('Y-m-d H:i:s')
        ));

        return $this->connection->lastInsertId();
    }
}
