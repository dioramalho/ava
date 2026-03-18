<?php

class User extends BaseModel
{
    public function findByLogin($login)
    {
        $statement = $this->connection->prepare('SELECT id, nome, login, senha, perfil FROM usuarios WHERE login = :login LIMIT 1');
        $statement->bindValue(':login', $login);
        $statement->execute();

        return $statement->fetch();
    }
}
