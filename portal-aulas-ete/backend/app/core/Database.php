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
        self::ensureTurmaArquivadaColumn();

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

        self::ensureTurmaArquivadaColumn();
    }

    /**
     * Bancos já criados não recebem o schema completo de novo.
     * A coluna entra só quando ainda não existe.
     */
    private static function ensureTurmaArquivadaColumn()
    {
        $driver = self::$connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $columns = self::$connection->query('PRAGMA table_info(turmas)')->fetchAll();
            foreach ($columns as $column) {
                if (isset($column['name']) && $column['name'] === 'arquivada') {
                    return;
                }
            }

            self::$connection->exec(
                'ALTER TABLE turmas ADD COLUMN arquivada INTEGER NOT NULL DEFAULT 0'
            );
            return;
        }

        $statement = self::$connection->query("SHOW COLUMNS FROM turmas LIKE 'arquivada'");
        if ($statement && $statement->fetch()) {
            return;
        }

        self::$connection->exec(
            'ALTER TABLE turmas ADD COLUMN arquivada TINYINT(1) NOT NULL DEFAULT 0'
        );
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
