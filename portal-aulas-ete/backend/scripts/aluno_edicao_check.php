<?php
/**
 * Verifica máscara/normalização de celular e e-mail, status editável
 * e redefinição da senha do aluno para @ETE2626.
 *
 * Uso (a partir de portal-aulas-ete/backend):
 *   php scripts/aluno_edicao_check.php
 *
 * A parte HTTP usa o Apache local. Se a API não responder, as checagens
 * de função ainda rodam e o script termina com falha só na etapa HTTP.
 */

$backendDir = dirname(__DIR__);
require_once $backendDir . '/app/core/AlunoFormato.php';
require_once $backendDir . '/app/core/PasswordHelper.php';

$failures = array();

function check($condition, $message, &$failures)
{
    if ($condition) {
        echo 'OK  ' . $message . PHP_EOL;
        return;
    }

    $failures[] = $message;
    echo 'FAIL ' . $message . PHP_EOL;
}

echo '== Funcoes de formato e senha ==' . PHP_EOL;

check(AlunoFormato::celular('81997941373') === '(81)99794-1373', '11 digitos viram (81)99794-1373', $failures);
check(AlunoFormato::celular('(81) 99794-1373') === '(81)99794-1373', 'celular com espaco normaliza sem espaco', $failures);
check(AlunoFormato::celular('(81)99794-1373') === '(81)99794-1373', 'celular ja mascarado permanece', $failures);
check(AlunoFormato::celular('1234') === '1234', 'quantidade diferente de 11 nao ganha mascara falsa', $failures);
check(AlunoFormato::celular('') === '', 'celular vazio permanece vazio', $failures);

check(AlunoFormato::email(' Nome.Sobrenome@Escola.com ') === 'nome.sobrenome@escola.com', 'e-mail escolar em minusculas', $failures);
check(AlunoFormato::email('nome sobrenome@escola.com') === 'nomesobrenome@escola.com', 'e-mail sem espacos', $failures);
check(AlunoFormato::email('nome+tag@escola.com') === 'nometag@escola.com', 'plus-addressing removido', $failures);
check(AlunoFormato::email('nome@@escola.com') === 'nome@escola.com', 'apenas um @', $failures);
check(filter_var(AlunoFormato::email('nome.sobrenome@escola.com'), FILTER_VALIDATE_EMAIL) !== false, 'e-mail normalizado passa em filter_var', $failures);
check(filter_var('nao-e-email', FILTER_VALIDATE_EMAIL) === false, 'texto sem @ falha na validacao', $failures);

$hash = PasswordHelper::hash('@ETE2626');
check($hash !== '@ETE2626', 'senha padrao nao fica em texto puro', $failures);
check(PasswordHelper::verify('@ETE2626', $hash) === true, 'hash confere com @ETE2626', $failures);
check(PasswordHelper::verify('1234', $hash) === false, 'outra senha nao confere com o hash padrao', $failures);

$node = nodeMaskCheck($failures);

echo PHP_EOL . '== API local ==' . PHP_EOL;
httpFlow($failures);

echo PHP_EOL;
if ($failures) {
    echo 'RESULTADO: ' . count($failures) . ' falha(s).' . PHP_EOL;
    foreach ($failures as $failure) {
        echo ' - ' . $failure . PHP_EOL;
    }
    exit(1);
}

echo 'RESULTADO: edicao do aluno ok.' . PHP_EOL;
exit(0);

function nodeMaskCheck(&$failures)
{
    $script = dirname(dirname(__DIR__)) . '/js/aluno-form-masks.js';

    $probe = shell_exec('node -v 2>&1');
    if (!is_string($probe) || strpos($probe, 'v') !== 0) {
        echo 'SKIP mascara JS no Node (node ausente)' . PHP_EOL;
        return;
    }

    $runner = tempnam(sys_get_temp_dir(), 'mask');
    $runnerJs = $runner . '.js';
    @unlink($runner);
    $code = 'var m=require(' . json_encode($script) . ');'
        . 'var ok=m.maskCelular("81997941373")==="(81)99794-1373"'
        . '&& m.maskCelular("(81) 99794-1373")==="(81)99794-1373"'
        . '&& m.maskEmail(" Nome.Sobrenome@Escola.COM ")==="nome.sobrenome@escola.com"'
        . '&& m.maskEmail("a b@x.com")==="ab@x.com"'
        . '&& m.emailValido("nome.sobrenome@escola.com")'
        . '&& !m.emailValido("nome sobrenome@escola.com")'
        . '&& !m.emailValido("sem-arroba");'
        . 'if(!ok){process.exit(1);}';
    file_put_contents($runnerJs, $code);

    $output = array();
    $exitCode = 1;
    exec('node ' . escapeshellarg($runnerJs) . ' 2>&1', $output, $exitCode);
    @unlink($runnerJs);
    check($exitCode === 0, 'mascara JS de celular e e-mail', $failures);
    if ($exitCode !== 0 && $output) {
        echo implode(PHP_EOL, $output) . PHP_EOL;
    }
}

function httpFlow(&$failures)
{
    $bases = array(
        'http://localhost/ava/portal-aulas-ete/backend/public/index.php?route=',
        'http://127.0.0.1/ava/portal-aulas-ete/backend/public/index.php?route='
    );

    $base = null;
    foreach ($bases as $candidate) {
        $health = httpRequest($candidate . '/health', 'GET', null, null);
        if ($health['status'] === 200 && strpos($health['body'], 'Backend online') !== false) {
            $base = $candidate;
            break;
        }
    }

    if ($base === null) {
        $failures[] = 'API local indisponivel';
        echo 'FAIL API local indisponivel' . PHP_EOL;
        return;
    }

    echo 'API ' . $base . PHP_EOL;

    $stamp = date('YmdHis');
    $email = 'aluno.edicao.' . $stamp . '@escola.test';
    $emailNovo = 'nome.sobrenome.' . $stamp . '@escola.test';
    $senhaInicial = 'senha123';
    $cookieProfessor = tempnam(sys_get_temp_dir(), 'prof');
    $cookieAluno = tempnam(sys_get_temp_dir(), 'aluno');
    $alunoId = 0;

    $cadastro = httpRequest($base . '/auth/cadastro-aluno', 'POST', array(
        'nome' => 'Aluno Edicao Teste',
        'celular' => '81988887777',
        'email' => $email,
        'senha' => $senhaInicial,
        'confirmacao_senha' => $senhaInicial
    ), null);
    check($cadastro['status'] === 201, 'cadastro de aluno de teste', $failures);

    $loginProfessor = httpRequest($base . '/auth/login', 'POST', array(
        'login' => 'professor',
        'senha' => '1234'
    ), $cookieProfessor);
    check($loginProfessor['status'] === 200, 'login do professor seed', $failures);

    $turmas = httpRequest($base . '/turmas', 'GET', null, $cookieProfessor);
    $turmaId = 0;
    if (isset($turmas['json']['data'][0]['id'])) {
        $turmaId = (int) $turmas['json']['data'][0]['id'];
    }
    check($turmaId > 0, 'existe turma para aprovar o aluno', $failures);

    $lista = httpRequest($base . '/alunos/pendentes', 'GET', null, $cookieProfessor);
    if (isset($lista['json']['data']) && is_array($lista['json']['data'])) {
        foreach ($lista['json']['data'] as $item) {
            if (isset($item['email']) && $item['email'] === $email) {
                $alunoId = (int) $item['id'];
            }
        }
    }
    check($alunoId > 0, 'aluno de teste esta pendente', $failures);

    if ($alunoId > 0 && $turmaId > 0) {
        $aprovar = httpRequest($base . '/alunos/aprovar', 'POST', array(
            'id' => $alunoId,
            'turma_id' => $turmaId
        ), $cookieProfessor);
        check($aprovar['status'] === 200, 'professor aprova o aluno de teste', $failures);
    }

    $semSessao = httpRequest($base . '/alunos/reset-senha', 'POST', array('id' => $alunoId), null);
    check($semSessao['status'] === 401, 'reset sem sessao responde 401', $failures);

    $loginAluno = httpRequest($base . '/auth/login', 'POST', array(
        'login' => $email,
        'senha' => $senhaInicial
    ), $cookieAluno);
    check($loginAluno['status'] === 200, 'aluno entra com a senha do cadastro', $failures);

    $comoAluno = httpRequest($base . '/alunos/reset-senha', 'POST', array('id' => $alunoId), $cookieAluno);
    check($comoAluno['status'] === 403, 'aluno nao redefine senha', $failures);

    $update = httpRequest($base . '/alunos/update', 'POST', array(
        'id' => $alunoId,
        'nome' => 'Aluno Edicao Teste',
        'email' => ' Nome.Sobrenome.' . $stamp . '@Escola.TEST ',
        'celular' => '81997941373',
        'turma_id' => $turmaId,
        'status' => 'recusado'
    ), $cookieProfessor);
    check($update['status'] === 200, 'update grava status, celular e e-mail', $failures);

    $recusado = httpRequest($base . '/auth/login', 'POST', array(
        'login' => $emailNovo,
        'senha' => $senhaInicial
    ), tempnam(sys_get_temp_dir(), 'blk'));
    check($recusado['status'] === 403, 'status recusado bloqueia o login', $failures);

    $encontrado = buscarAluno($base, $cookieProfessor, $alunoId);
    check($encontrado && $encontrado['status'] === 'recusado', 'status recusado persistido na lista', $failures);
    check($encontrado && $encontrado['celular'] === '(81)99794-1373', 'celular persistido como (81)99794-1373', $failures);
    check($encontrado && $encontrado['email'] === $emailNovo, 'e-mail persistido em minusculas', $failures);

    $invalido = httpRequest($base . '/alunos/update', 'POST', array(
        'id' => $alunoId,
        'nome' => 'Aluno Edicao Teste',
        'email' => 'sem-arroba',
        'celular' => '(81)99794-1373',
        'turma_id' => $turmaId,
        'status' => 'recusado'
    ), $cookieProfessor);
    check($invalido['status'] === 422, 'e-mail invalido responde 422', $failures);

    $parcial = httpRequest($base . '/alunos/update', 'POST', array(
        'id' => $alunoId,
        'nome' => 'Aluno Edicao Teste',
        'email' => $emailNovo,
        'celular' => '81997',
        'turma_id' => $turmaId,
        'status' => 'recusado'
    ), $cookieProfessor);
    check($parcial['status'] === 422, 'celular incompleto responde 422', $failures);

    $reativar = httpRequest($base . '/alunos/update', 'POST', array(
        'id' => $alunoId,
        'nome' => 'Aluno Edicao Teste',
        'email' => $emailNovo,
        'celular' => '(81)99794-1373',
        'turma_id' => $turmaId,
        'status' => 'aprovado'
    ), $cookieProfessor);
    check($reativar['status'] === 200, 'status volta para aprovado', $failures);

    $reset = httpRequest($base . '/alunos/reset-senha', 'POST', array('id' => $alunoId), $cookieProfessor);
    check($reset['status'] === 200, 'professor redefine a senha', $failures);

    $hashOk = verificarHashNoBanco($alunoId);
    check($hashOk, 'hash no banco confere com @ETE2626 e nao e texto puro', $failures);

    $loginPadrao = httpRequest($base . '/auth/login', 'POST', array(
        'login' => $emailNovo,
        'senha' => '@ETE2626'
    ), tempnam(sys_get_temp_dir(), 'ok'));
    check($loginPadrao['status'] === 200, 'aluno entra com @ETE2626', $failures);

    $loginAntiga = httpRequest($base . '/auth/login', 'POST', array(
        'login' => $emailNovo,
        'senha' => $senhaInicial
    ), tempnam(sys_get_temp_dir(), 'old'));
    check($loginAntiga['status'] === 401, 'senha anterior deixa de valer', $failures);

    if ($alunoId > 0) {
        $delete = httpRequest($base . '/alunos/delete', 'POST', array('id' => $alunoId), $cookieProfessor);
        check($delete['status'] === 200, 'aluno de teste removido', $failures);
    }

    @unlink($cookieProfessor);
    @unlink($cookieAluno);
}

function buscarAluno($base, $cookie, $id)
{
    $lista = httpRequest($base . '/alunos', 'GET', null, $cookie);
    if (!isset($lista['json']['data']) || !is_array($lista['json']['data'])) {
        return null;
    }

    foreach ($lista['json']['data'] as $item) {
        if ((int) $item['id'] === (int) $id) {
            return $item;
        }
    }

    return null;
}

function verificarHashNoBanco($alunoId)
{
    if ($alunoId <= 0) {
        return false;
    }

    require_once dirname(__DIR__) . '/app/core/Autoloader.php';
    $user = new User();
    $hash = $user->findSenhaHash($alunoId);
    if (!$hash || $hash === '@ETE2626') {
        return false;
    }

    return PasswordHelper::verify('@ETE2626', $hash);
}

function httpRequest($url, $method, $body, $cookieFile)
{
    $ch = curl_init($url);
    $headers = array('Accept: application/json');
    $options = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 20
    );

    if ($cookieFile) {
        $options[CURLOPT_COOKIEJAR] = $cookieFile;
        $options[CURLOPT_COOKIEFILE] = $cookieFile;
    }

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $options[CURLOPT_HTTPHEADER] = $headers;
        $options[CURLOPT_POSTFIELDS] = json_encode($body);
    }

    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json = json_decode($raw, true);

    return array(
        'status' => $status,
        'body' => is_string($raw) ? $raw : '',
        'json' => is_array($json) ? $json : null
    );
}
