<?php

return array(
    'db' => array(
        'driver' => getenv('APP_DB_DRIVER') ? getenv('APP_DB_DRIVER') : 'mysql',
        'host' => getenv('APP_DB_HOST') ? getenv('APP_DB_HOST') : '127.0.0.1',
        'port' => getenv('APP_DB_PORT') ? getenv('APP_DB_PORT') : '3306',
        'dbname' => getenv('APP_DB_NAME') ? getenv('APP_DB_NAME') : 'portal_aulas_ete',
        'charset' => 'utf8mb4',
        'username' => getenv('APP_DB_USER') ? getenv('APP_DB_USER') : 'root',
        'password' => getenv('APP_DB_PASSWORD') ? getenv('APP_DB_PASSWORD') : '',
        'database' => getenv('APP_DB_DATABASE') ? getenv('APP_DB_DATABASE') : ''
    ),
    'app' => array(
        'base_path' => dirname(__DIR__),
        'upload_dir' => dirname(__DIR__) . '/storage/uploads',
        'upload_url' => '/portal-aulas-ete/backend/storage/uploads'
    )
);
