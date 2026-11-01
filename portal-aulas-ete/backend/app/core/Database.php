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
        self::ensureTrabalhoTables();

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
        self::ensureTrabalhoTables();
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

    /**
     * Cria as tabelas de trabalho em bancos que já existiam antes da funcionalidade.
     */
    private static function ensureTrabalhoTables()
    {
        $driver = self::$connection->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            self::$connection->exec(
                'CREATE TABLE IF NOT EXISTS trabalhos (
                  id INTEGER PRIMARY KEY AUTOINCREMENT,
                  titulo TEXT NOT NULL,
                  descricao TEXT NOT NULL,
                  data_entrega TEXT NOT NULL,
                  turma_id INTEGER NOT NULL,
                  aula_id INTEGER NOT NULL,
                  arquivo_nome TEXT DEFAULT NULL,
                  arquivo_caminho TEXT DEFAULT NULL,
                  arquivo_extensao TEXT DEFAULT NULL,
                  video_url TEXT DEFAULT NULL,
                  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  FOREIGN KEY (turma_id) REFERENCES turmas (id),
                  FOREIGN KEY (aula_id) REFERENCES aulas (id)
                )'
            );
            self::$connection->exec(
                'CREATE TABLE IF NOT EXISTS trabalho_alunos (
                  id INTEGER PRIMARY KEY AUTOINCREMENT,
                  trabalho_id INTEGER NOT NULL,
                  aluno_id INTEGER NOT NULL,
                  status TEXT NOT NULL DEFAULT \'pendente\',
                  data_entrega_vigente TEXT NOT NULL,
                  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  UNIQUE (trabalho_id, aluno_id),
                  FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id) ON DELETE CASCADE,
                  FOREIGN KEY (aluno_id) REFERENCES usuarios (id)
                )'
            );
            self::$connection->exec(
                'CREATE TABLE IF NOT EXISTS trabalho_eventos (
                  id INTEGER PRIMARY KEY AUTOINCREMENT,
                  trabalho_aluno_id INTEGER NOT NULL,
                  tipo TEXT NOT NULL,
                  autor_id INTEGER DEFAULT NULL,
                  autor_perfil TEXT NOT NULL,
                  texto TEXT DEFAULT NULL,
                  arquivo_nome TEXT DEFAULT NULL,
                  arquivo_caminho TEXT DEFAULT NULL,
                  arquivo_extensao TEXT DEFAULT NULL,
                  data_entrega TEXT DEFAULT NULL,
                  status TEXT DEFAULT NULL,
                  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  FOREIGN KEY (trabalho_aluno_id) REFERENCES trabalho_alunos (id) ON DELETE CASCADE
                )'
            );
            return;
        }

        self::$connection->exec(
            'CREATE TABLE IF NOT EXISTS trabalhos (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              titulo VARCHAR(180) NOT NULL,
              descricao TEXT NOT NULL,
              data_entrega DATE NOT NULL,
              turma_id INT UNSIGNED NOT NULL,
              aula_id INT UNSIGNED NOT NULL,
              arquivo_nome VARCHAR(255) DEFAULT NULL,
              arquivo_caminho VARCHAR(255) DEFAULT NULL,
              arquivo_extensao VARCHAR(10) DEFAULT NULL,
              video_url VARCHAR(500) DEFAULT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY idx_trabalhos_turma (turma_id),
              KEY idx_trabalhos_aula (aula_id),
              CONSTRAINT fk_trabalhos_turma FOREIGN KEY (turma_id) REFERENCES turmas (id),
              CONSTRAINT fk_trabalhos_aula FOREIGN KEY (aula_id) REFERENCES aulas (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$connection->exec(
            'CREATE TABLE IF NOT EXISTS trabalho_alunos (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              trabalho_id INT UNSIGNED NOT NULL,
              aluno_id INT UNSIGNED NOT NULL,
              status VARCHAR(20) NOT NULL DEFAULT \'pendente\',
              data_entrega_vigente DATE NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              UNIQUE KEY uk_trabalho_aluno (trabalho_id, aluno_id),
              KEY idx_trabalho_alunos_aluno (aluno_id),
              CONSTRAINT fk_trabalho_alunos_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id) ON DELETE CASCADE,
              CONSTRAINT fk_trabalho_alunos_aluno FOREIGN KEY (aluno_id) REFERENCES usuarios (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        self::$connection->exec(
            'CREATE TABLE IF NOT EXISTS trabalho_eventos (
              id INT UNSIGNED NOT NULL AUTO_INCREMENT,
              trabalho_aluno_id INT UNSIGNED NOT NULL,
              tipo VARCHAR(20) NOT NULL,
              autor_id INT UNSIGNED DEFAULT NULL,
              autor_perfil VARCHAR(20) NOT NULL,
              texto TEXT DEFAULT NULL,
              arquivo_nome VARCHAR(255) DEFAULT NULL,
              arquivo_caminho VARCHAR(255) DEFAULT NULL,
              arquivo_extensao VARCHAR(10) DEFAULT NULL,
              data_entrega DATE DEFAULT NULL,
              status VARCHAR(20) DEFAULT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (id),
              KEY idx_trabalho_eventos_participacao (trabalho_aluno_id),
              CONSTRAINT fk_trabalho_eventos_participacao FOREIGN KEY (trabalho_aluno_id) REFERENCES trabalho_alunos (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
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
