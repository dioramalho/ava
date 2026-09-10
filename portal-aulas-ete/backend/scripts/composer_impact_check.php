<?php
/**
 * Teste básico de impacto da instalação do Composer.
 *
 * Compara o autoload classmap do Composer com o Autoloader.php interno
 * e as respostas HTTP da API nos dois modos.
 *
 * Uso (a partir de portal-aulas-ete/backend):
 *   php scripts/composer_impact_check.php
 */

$backendDir = dirname(__DIR__);
$vendorDir = $backendDir . '/vendor';
$vendorAutoload = $vendorDir . '/autoload.php';
$legacyAutoload = $backendDir . '/app/core/Autoloader.php';
$classmapFile = $vendorDir . '/composer/autoload_classmap.php';
$failures = array();
$notes = array();

function fail(&$failures, $message)
{
    $failures[] = $message;
}

function info($message)
{
    echo $message . PHP_EOL;
}

function appClassFiles($backendDir)
{
    $roots = array(
        $backendDir . '/app/core',
        $backendDir . '/app/controllers',
        $backendDir . '/app/models'
    );
    $classes = array();

    foreach ($roots as $root) {
        $files = glob($root . '/*.php');
        if (!is_array($files)) {
            continue;
        }
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            if ($contents === false) {
                continue;
            }
            if (!preg_match('/^\s*class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $match)) {
                continue;
            }
            $classes[$match[1]] = $file;
        }
    }

    ksort($classes);
    return $classes;
}

function composerAppClasses($classmapFile)
{
    if (!is_file($classmapFile)) {
        return array();
    }

    $classmap = require $classmapFile;
    $classes = array();

    foreach ($classmap as $className => $file) {
        if (strpos($className, '\\') !== false) {
            continue;
        }
        $classes[$className] = $file;
    }

    ksort($classes);
    return $classes;
}

function loadClassesWithAutoload($autoloadFile, $classNames)
{
    $payload = array(
        'autoload' => $autoloadFile,
        'classes' => array_values($classNames)
    );
    $encoded = base64_encode(json_encode($payload));
    $runner = '<?php
$payload = json_decode(base64_decode($argv[1]), true);
require $payload["autoload"];
$missing = array();
foreach ($payload["classes"] as $className) {
    if (!class_exists($className)) {
        $missing[] = $className;
    }
}
echo json_encode($missing);
';
    $temp = tempnam(sys_get_temp_dir(), 'ava_autoload_');
    file_put_contents($temp, $runner);
    $cmd = 'php ' . escapeshellarg($temp) . ' ' . escapeshellarg($encoded) . ' 2>&1';
    $output = shell_exec($cmd);
    @unlink($temp);

    $missing = json_decode(trim((string) $output), true);
    if (!is_array($missing)) {
        return array('PARSE_ERROR: ' . trim((string) $output));
    }
    return $missing;
}

function httpGet($url, $method)
{
    $context = stream_context_create(array(
        'http' => array(
            'method' => $method,
            'ignore_errors' => true,
            'timeout' => 5,
            'header' => "Accept: application/json\r\n"
        )
    ));

    $body = @file_get_contents($url, false, $context);
    $status = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $headerLine) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/', $headerLine, $match)) {
                $status = (int) $match[1];
            }
        }
    }

    return array(
        'status' => $status,
        'body' => $body === false ? '' : $body
    );
}

function startServer($docRoot, $port)
{
    $log = tempnam(sys_get_temp_dir(), 'ava_php_server_');
    $cmd = sprintf(
        'php -S 127.0.0.1:%d -t %s > %s 2>&1 & echo $!',
        $port,
        escapeshellarg($docRoot),
        escapeshellarg($log)
    );
    $pid = (int) trim(shell_exec($cmd));
    $ready = false;
    $deadline = time() + 8;

    while (time() < $deadline) {
        $errno = 0;
        $errstr = '';
        $socket = @fsockopen('127.0.0.1', $port, $errno, $errstr, 0.25);
        if (is_resource($socket)) {
            fclose($socket);
            $ready = true;
            break;
        }
        usleep(100000);
    }

    return array($pid, $ready, $log);
}

function stopServer($pid)
{
    if ($pid > 0) {
        exec('kill ' . (int) $pid . ' 2>/dev/null');
    }
}

function captureApi($docRoot, $port)
{
    list($pid, $ready, $log) = startServer($docRoot, $port);
    if (!$ready) {
        $logText = is_file($log) ? file_get_contents($log) : '';
        stopServer($pid);
        @unlink($log);
        return array('error' => 'Servidor PHP não subiu em 127.0.0.1:' . $port . ' ' . $logText);
    }

    $base = 'http://127.0.0.1:' . $port . '/backend/public/index.php';
    $result = array(
        'health' => httpGet($base . '?route=/health', 'GET'),
        'options' => httpGet($base . '?route=/health', 'OPTIONS'),
        'missing_route' => httpGet($base . '?route=/rota-inexistente-composer', 'GET')
    );

    stopServer($pid);
    @unlink($log);
    return $result;
}

info('=== Teste de impacto: Composer vs Autoloader ===');
info('');

if (!is_file($vendorAutoload)) {
    fail($failures, 'vendor/autoload.php não encontrado. Rode composer install em portal-aulas-ete/backend.');
}

if (!is_file($legacyAutoload)) {
    fail($failures, 'Autoloader.php interno não encontrado.');
}

$appClasses = appClassFiles($backendDir);
$composerClasses = composerAppClasses($classmapFile);
$appClassNames = array_keys($appClasses);
$composerClassNames = array_keys($composerClasses);

info('Classes da aplicação: ' . count($appClasses));
info('Classes no classmap do Composer (sem namespaces): ' . count($composerClasses));

$missingInComposer = array_values(array_diff($appClassNames, $composerClassNames));
$extraInComposer = array_values(array_diff($composerClassNames, $appClassNames));

if ($missingInComposer) {
    fail($failures, 'Classes da app ausentes no classmap do Composer: ' . implode(', ', $missingInComposer));
} else {
    info('Classmap do Composer cobre todas as classes da aplicação.');
}

if ($extraInComposer) {
    fail($failures, 'Classmap do Composer tem classes extras da app: ' . implode(', ', $extraInComposer));
}

if (is_file($vendorAutoload)) {
    $missingComposerLoad = loadClassesWithAutoload($vendorAutoload, $appClassNames);
    if ($missingComposerLoad) {
        fail($failures, 'Composer não carregou: ' . implode(', ', $missingComposerLoad));
    } else {
        info('Composer autoload carregou todas as classes da aplicação.');
    }
}

$missingLegacyLoad = loadClassesWithAutoload($legacyAutoload, $appClassNames);
if ($missingLegacyLoad) {
    fail($failures, 'Autoloader interno não carregou: ' . implode(', ', $missingLegacyLoad));
} else {
    info('Autoloader interno carregou todas as classes da aplicação.');
}

$docRoot = dirname($backendDir);
$portComposer = 18081;
$portLegacy = 18082;

info('');
info('Capturando API com vendor/ (Composer)...');
$composerHttp = captureApi($docRoot, $portComposer);

$vendorBackup = $backendDir . '/vendor.impact-backup';
$movedVendor = false;
if (is_dir($vendorDir)) {
    if (is_dir($vendorBackup)) {
        fail($failures, 'Já existe vendor.impact-backup; abortando o teste HTTP no modo legado.');
    } else {
        $movedVendor = @rename($vendorDir, $vendorBackup);
        if (!$movedVendor) {
            fail($failures, 'Não foi possível ocultar vendor/ para testar o Autoloader interno.');
        }
    }
}

info('Capturando API sem vendor/ (Autoloader interno)...');
$legacyHttp = captureApi($docRoot, $portLegacy);

if ($movedVendor && is_dir($vendorBackup) && !is_dir($vendorDir)) {
    if (!@rename($vendorBackup, $vendorDir)) {
        fail($failures, 'Não foi possível restaurar vendor/ após o teste.');
    }
}

if (isset($composerHttp['error'])) {
    fail($failures, 'HTTP Composer: ' . $composerHttp['error']);
}
if (isset($legacyHttp['error'])) {
    fail($failures, 'HTTP legado: ' . $legacyHttp['error']);
}

if (!isset($composerHttp['error']) && !isset($legacyHttp['error'])) {
    foreach (array('health', 'options', 'missing_route') as $case) {
        $left = $composerHttp[$case];
        $right = $legacyHttp[$case];
        info($case . ' Composer  => HTTP ' . $left['status'] . ' ' . $left['body']);
        info($case . ' Autoloader => HTTP ' . $right['status'] . ' ' . $right['body']);

        if ($left['status'] !== $right['status'] || $left['body'] !== $right['body']) {
            fail($failures, 'Resposta diferente em ' . $case . '.');
        }
    }

    $health = $composerHttp['health'];
    if ($health['status'] !== 200 || strpos($health['body'], '"Backend online."') === false) {
        fail($failures, 'GET /health não retornou 200 com mensagem esperada.');
    } else {
        $notes[] = 'GET /health permanece 200 com a mesma mensagem nos dois modos.';
    }

    if ($composerHttp['options']['status'] !== 204) {
        fail($failures, 'OPTIONS deveria continuar retornando 204.');
    }
}

info('');
if ($failures) {
    info('RESULTADO: impacto ou falha detectada.');
    foreach ($failures as $failure) {
        info('- ' . $failure);
    }
    exit(1);
}

info('RESULTADO: nenhum impacto funcional detectado.');
info('O Composer carrega o mesmo conjunto de classes e a API responde igual ao Autoloader interno.');
foreach ($notes as $note) {
    info('- ' . $note);
}
exit(0);
