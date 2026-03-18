<?php

class Database
{
    /** @var PDO */
    private static $connection;

    /**
     * @return PDO
     */
    public static function getConnection()
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require dirname(dirname(__DIR__)) . '/config/config.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['dbname'],
            $db['charset']
        );

        self::$connection = new PDO($dsn, $db['username'], $db['password']);
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return self::$connection;
    }
}
