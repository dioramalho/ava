<?php

return array(
    'db' => array(
        'host' => getenv('PORTAL_DB_HOST') ? getenv('PORTAL_DB_HOST') : '127.0.0.1',
        'port' => getenv('PORTAL_DB_PORT') ? getenv('PORTAL_DB_PORT') : '3306',
        'dbname' => getenv('PORTAL_DB_NAME') ? getenv('PORTAL_DB_NAME') : 'portal_aulas_ete',
        'charset' => getenv('PORTAL_DB_CHARSET') ? getenv('PORTAL_DB_CHARSET') : 'utf8mb4',
        'username' => getenv('PORTAL_DB_USER') ? getenv('PORTAL_DB_USER') : 'root',
        'password' => getenv('PORTAL_DB_PASSWORD') ? getenv('PORTAL_DB_PASSWORD') : ''
    ),
    'app' => array(
        'base_path' => dirname(__DIR__),
        'upload_dir' => dirname(__DIR__) . '/storage/uploads',
        'upload_url' => '/portal-aulas-ete/backend/storage/uploads'
    )
);
