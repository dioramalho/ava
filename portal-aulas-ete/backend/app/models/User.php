<?php

class User extends BaseModel
{
    public function findByLogin($login)
    {
        $statement = $this->connection->prepare(
            'SELECT usuarios.id, usuarios.nome, usuarios.login, usuarios.senha, usuarios.perfil,
                    usuarios.email, usuarios.celular, usuarios.status, usuarios.turma_id,
                    turmas.codigo AS turma_codigo, turmas.titulo AS turma_titulo
             FROM usuarios
             LEFT JOIN turmas ON turmas.id = usuarios.turma_id
             WHERE usuarios.login = :login
             LIMIT 1'
        );
        $statement->bindValue(':login', $login);
        $statement->execute();

        return $statement->fetch();
    }

    public function findByEmail($email)
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM usuarios WHERE email = :email LIMIT 1'
        );
        $statement->bindValue(':email', $email);
        $statement->execute();

        return $statement->fetch();
    }

    public function findById($id)
    {
        $statement = $this->connection->prepare(
            'SELECT id, nome, login, perfil, email, celular, status, turma_id
             FROM usuarios
             WHERE id = :id
             LIMIT 1'
        );
        $statement->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetch();
    }

    public function createAlunoPendente($data)
    {
        $statement = $this->connection->prepare(
            'INSERT INTO usuarios (nome, login, senha, perfil, email, celular, status)
             VALUES (:nome, :login, :senha, :perfil, :email, :celular, :status)'
        );
        $statement->execute(array(
            ':nome' => $data['nome'],
            ':login' => $data['login'],
            ':senha' => $data['senha'],
            ':perfil' => 'aluno',
            ':email' => $data['email'],
            ':celular' => $data['celular'],
            ':status' => 'pendente'
        ));

        return $this->connection->lastInsertId();
    }

    public function listPendentes()
    {
        $sql = 'SELECT id, nome, email, celular, created_at
                FROM usuarios
                WHERE perfil = :perfil AND status = :status
                ORDER BY created_at ASC';
        $statement = $this->connection->prepare($sql);
        $statement->execute(array(
            ':perfil' => 'aluno',
            ':status' => 'pendente'
        ));

        return $statement->fetchAll();
    }

    public function listAprovados()
    {
        $sql = 'SELECT usuarios.id, usuarios.nome, usuarios.email, usuarios.celular, usuarios.status,
                       turmas.id AS turma_id, turmas.codigo AS turma_codigo, turmas.titulo AS turma_titulo
                FROM usuarios
                INNER JOIN turmas ON turmas.id = usuarios.turma_id
                WHERE usuarios.perfil = :perfil AND usuarios.status = :status
                ORDER BY usuarios.nome ASC';
        $statement = $this->connection->prepare($sql);
        $statement->execute(array(
            ':perfil' => 'aluno',
            ':status' => 'aprovado'
        ));

        return $statement->fetchAll();
    }

    public function approve($id, $turmaId)
    {
        $statement = $this->connection->prepare(
            'UPDATE usuarios
             SET status = :status, turma_id = :turma_id
             WHERE id = :id AND perfil = :perfil AND status = :atual'
        );

        return $statement->execute(array(
            ':status' => 'aprovado',
            ':turma_id' => (int) $turmaId,
            ':id' => (int) $id,
            ':perfil' => 'aluno',
            ':atual' => 'pendente'
        ));
    }

    public function reject($id)
    {
        $statement = $this->connection->prepare(
            'UPDATE usuarios
             SET status = :status, turma_id = NULL
             WHERE id = :id AND perfil = :perfil AND status = :atual'
        );

        return $statement->execute(array(
            ':status' => 'recusado',
            ':id' => (int) $id,
            ':perfil' => 'aluno',
            ':atual' => 'pendente'
        ));
    }

    public function countAprovados()
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total FROM usuarios WHERE perfil = :perfil AND status = :status'
        );
        $statement->execute(array(':perfil' => 'aluno', ':status' => 'aprovado'));
        $row = $statement->fetch();

        return $row ? (int) $row['total'] : 0;
    }

    public function countPendentes()
    {
        $statement = $this->connection->prepare(
            'SELECT COUNT(*) AS total FROM usuarios WHERE perfil = :perfil AND status = :status'
        );
        $statement->execute(array(':perfil' => 'aluno', ':status' => 'pendente'));
        $row = $statement->fetch();

        return $row ? (int) $row['total'] : 0;
    }

    public function toSessionUser($user)
    {
        $turmaId = isset($user['turma_id']) && $user['turma_id'] !== null && $user['turma_id'] !== ''
            ? (int) $user['turma_id']
            : null;

        return array(
            'id' => (int) $user['id'],
            'nome' => $user['nome'],
            'login' => $user['login'],
            'perfil' => $user['perfil'],
            'turma_id' => $turmaId,
            'turma_codigo' => isset($user['turma_codigo']) ? $user['turma_codigo'] : '',
            'turma_titulo' => isset($user['turma_titulo']) ? $user['turma_titulo'] : ''
        );
    }
}
