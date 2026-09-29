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

$localMail = array();
$localMailFile = __DIR__ . '/mail.local.php';
if (is_file($localMailFile)) {
    $loadedMail = require $localMailFile;
    if (is_array($loadedMail)) {
        $localMail = $loadedMail;
    }
}

$mailEncryption = getenv('PORTAL_MAIL_ENCRYPTION');
if ($mailEncryption === false || $mailEncryption === '') {
    $mailEncryption = isset($localMail['encryption']) ? $localMail['encryption'] : 'tls';
}

$mailHost = getenv('PORTAL_MAIL_HOST');
if ($mailHost === false || $mailHost === '') {
    $mailHost = isset($localMail['host']) ? $localMail['host'] : '';
}

$mailPort = getenv('PORTAL_MAIL_PORT');
if ($mailPort === false || $mailPort === '') {
    $mailPort = isset($localMail['port']) ? $localMail['port'] : 587;
}

$mailUsername = getenv('PORTAL_MAIL_USERNAME');
if ($mailUsername === false || $mailUsername === '') {
    $mailUsername = isset($localMail['username']) ? $localMail['username'] : '';
}

$mailPassword = getenv('PORTAL_MAIL_PASSWORD');
if ($mailPassword === false || $mailPassword === '') {
    $mailPassword = isset($localMail['password']) ? $localMail['password'] : '';
}

$mailFrom = getenv('PORTAL_MAIL_FROM');
if ($mailFrom === false || $mailFrom === '') {
    $mailFrom = isset($localMail['from_address']) ? $localMail['from_address'] : '';
}

$mailFromName = getenv('PORTAL_MAIL_FROM_NAME');
if ($mailFromName === false || $mailFromName === '') {
    $mailFromName = isset($localMail['from_name']) ? $localMail['from_name'] : 'Portal de Aulas ETE';
}

$mail = array(
    'host' => $mailHost,
    'port' => (int) $mailPort,
    'username' => $mailUsername,
    'password' => $mailPassword,
    'encryption' => $mailEncryption,
    'from_address' => $mailFrom,
    'from_name' => $mailFromName
);

if (strtolower($dbDriver) === 'sqlite') {
    return array(
        'db' => array(
            'driver' => 'sqlite',
            'database' => dirname(__DIR__) . '/storage/database.sqlite'
        ),
        'app' => $app,
        'mail' => $mail
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
    'app' => $app,
    'mail' => $mail
);
