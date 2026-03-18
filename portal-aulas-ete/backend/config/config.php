<?php

return array(
    'db' => array(
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'portal_aulas_ete',
        'charset' => 'utf8mb4',
        'username' => 'root',
        'password' => ''
    ),
    'app' => array(
        'base_path' => dirname(__DIR__),
        'upload_dir' => dirname(__DIR__) . '/storage/uploads',
        'upload_url' => '/portal-aulas-ete/backend/storage/uploads'
    )
);
