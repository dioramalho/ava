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

        if (!in_array('mysql', PDO::getAvailableDrivers(), true)) {
            throw new PDOException(
                'PDO MySQL indisponível. Ative a extensão pdo_mysql ou defina PORTAL_DB_DRIVER=sqlite.'
            );
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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = $names;
        }

        self::$connection = new PDO($dsn, $db['username'], $db['password'], $options);
        self::$connection->exec($names);
        self::ensureProfessorSeed(self::$connection);

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

        if (!$isNewDatabase && !self::sqliteHasUsuariosTable(self::$connection)) {
            $isNewDatabase = true;
        }

        if ($isNewDatabase) {
            self::bootstrapSqlite(self::$connection);
        }

        self::ensureProfessorSeed(self::$connection);
    }

    private static function sqliteHasUsuariosTable(PDO $connection)
    {
        $statement = $connection->query(
            "SELECT name FROM sqlite_master WHERE type='table' AND name='usuarios' LIMIT 1"
        );

        return $statement && $statement->fetch();
    }

    private static function ensureProfessorSeed(PDO $connection)
    {
        try {
            $statement = $connection->prepare('SELECT id FROM usuarios WHERE login = :login LIMIT 1');
            $statement->execute(array(':login' => 'professor'));
            if ($statement->fetch()) {
                return;
            }

            $insert = $connection->prepare(
                'INSERT INTO usuarios (nome, login, senha, perfil, status)
                 VALUES (:nome, :login, :senha, :perfil, :status)'
            );
            $insert->execute(array(
                ':nome' => 'Professor Responsável',
                ':login' => 'professor',
                ':senha' => '7110eda4d09e062aa5e4a390b0a572ac0d2c0220',
                ':perfil' => 'professor',
                ':status' => 'aprovado'
            ));
        } catch (Exception $exception) {
            // Schema ausente: o bootstrap SQLite ou o SQL MySQL precisam existir antes do seed.
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
