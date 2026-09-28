<?php

session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$composerAutoload = dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
} else {
    require_once dirname(__DIR__) . '/app/core/Autoloader.php';
}

try {
    $request = new Request();
    $app = new App($request);
    $app->run();
} catch (Exception $exception) {
    error_log('Portal API: ' . $exception->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array(
        'success' => false,
        'message' => 'Erro interno ao processar a requisição.'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}
