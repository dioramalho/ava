<?php

class BaseModel
{
    /** @var PDO */
    protected $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }
}
