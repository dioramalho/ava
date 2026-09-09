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
        $driver = isset($db['driver']) ? strtolower($db['driver']) : 'mysql';

        if ($driver === 'sqlite') {
            self::connectSqlite($db['database']);
            return self::$connection;
        }

        $charset = isset($db['charset']) ? $db['charset'] : 'utf8mb4';
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['dbname'],
            $charset
        );

        $names = 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci';
        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => $names
        );

        self::$connection = new PDO($dsn, $db['username'], $db['password'], $options);
        self::$connection->exec($names);

        return self::$connection;
    }

    private static function connectSqlite($databasePath)
    {
        $directory = dirname($databasePath);
        $isNewDatabase = !file_exists($databasePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        self::$connection = new PDO('sqlite:' . $databasePath);
        self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        self::$connection->exec('PRAGMA foreign_keys = ON');

        if ($isNewDatabase) {
            self::bootstrapSqlite(self::$connection);
        }
    }

    private static function bootstrapSqlite(PDO $connection)
    {
        $schemaPath = dirname(dirname(__DIR__)) . '/config/sqlite_schema.sql';
        $schema = file_get_contents($schemaPath);

        if ($schema === false) {
            throw new Exception('Não foi possível carregar o schema SQLite inicial.');
        }

        $connection->exec($schema);
    }
}
