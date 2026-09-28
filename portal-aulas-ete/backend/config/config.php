<?php

$dbDriver = getenv('PORTAL_DB_DRIVER');
if (!$dbDriver) {
    $dbDriver = 'mysql';
}

$app = array(
    'base_path' => dirname(__DIR__),
    'upload_dir' => dirname(__DIR__) . '/storage/uploads',
    'upload_url' => '/portal-aulas-ete/backend/storage/uploads',
    'max_upload_bytes' => 10 * 1024 * 1024
);

if (strtolower($dbDriver) === 'sqlite') {
    return array(
        'db' => array(
            'driver' => 'sqlite',
            'database' => dirname(__DIR__) . '/storage/database.sqlite'
        ),
        'app' => $app
    );
}

return array(
    'db' => array(
        'driver' => 'mysql',
        'host' => getenv('PORTAL_DB_HOST') ? getenv('PORTAL_DB_HOST') : 'localhost',
        'port' => getenv('PORTAL_DB_PORT') ? getenv('PORTAL_DB_PORT') : '3306',
        'dbname' => getenv('PORTAL_DB_NAME') ? getenv('PORTAL_DB_NAME') : 'portal_aulas_ete',
        'charset' => 'utf8mb4',
        'username' => getenv('PORTAL_DB_USER') ? getenv('PORTAL_DB_USER') : 'root',
        'password' => getenv('PORTAL_DB_PASSWORD') ? getenv('PORTAL_DB_PASSWORD') : ''
    ),
    'app' => $app
);
