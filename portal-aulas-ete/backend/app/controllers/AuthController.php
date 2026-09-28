<?php

class AuthController extends Controller
{
    public function health()
    {
        $this->json(array(
            'success' => true,
            'message' => 'Backend online.'
        ), 200);
    }

    public function login(Request $request)
    {
        $login = $request->input('login', '');
        $senha = $request->input('senha', '');

        if ($login === '' || $senha === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Login e senha são obrigatórios.'
            ), 422);
        }

        $userModel = new User();
        $user = $userModel->findByLogin($login);
        if (!$user) {
            $user = $userModel->findByLogin(strtolower($login));
        }

        if (!$user || !PasswordHelper::verify($senha, $user['senha'])) {
            $this->json(array(
                'success' => false,
                'message' => 'Credenciais inválidas.'
            ), 401);
        }

        if ($user['perfil'] === 'aluno' && $user['status'] !== 'aprovado') {
            $recusado = $user['status'] === 'recusado';
            $this->json(array(
                'success' => false,
                'message' => $recusado
                    ? 'Sua solicitação de acesso foi recusada.'
                    : 'Seu acesso ainda não foi aprovado pelo professor.',
                'code' => $recusado ? 'aluno_recusado' : 'aluno_pendente'
            ), 403);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = $userModel->toSessionUser($user);

        $this->json(array(
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data' => $_SESSION['user']
        ), 200);
    }

    public function cadastroAluno(Request $request)
    {
        $nome = $request->input('nome', '');
        $celular = $request->input('celular', '');
        $email = strtolower($request->input('email', ''));
        $senha = $request->input('senha', '');
        $confirmacao = $request->input('confirmacao_senha', '');

        if ($nome === '' || $celular === '' || $email === '' || $senha === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Nome, celular, e-mail e senha são obrigatórios.'
            ), 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe um e-mail válido.'
            ), 422);
        }

        if (strlen($senha) < 6) {
            $this->json(array(
                'success' => false,
                'message' => 'A senha deve ter pelo menos 6 caracteres.'
            ), 422);
        }

        if ($senha !== $confirmacao) {
            $this->json(array(
                'success' => false,
                'message' => 'A confirmação de senha não confere.'
            ), 422);
        }

        $userModel = new User();
        if ($userModel->findByLogin($email) || $userModel->findByEmail($email)) {
            $this->json(array(
                'success' => false,
                'message' => 'Já existe uma solicitação ou conta com este e-mail.'
            ), 409);
        }

        try {
            $id = $userModel->createAlunoPendente(array(
                'nome' => $nome,
                'login' => $email,
                'senha' => PasswordHelper::hash($senha),
                'email' => $email,
                'celular' => $celular
            ));
        } catch (PDOException $exception) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível registrar a solicitação. Tente outro e-mail.'
            ), 409);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Solicitação enviada. Aguarde a aprovação do professor.',
            'id' => (int) $id
        ), 201);
    }

    public function logout()
    {
        $_SESSION = array();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();

        $this->json(array(
            'success' => true,
            'message' => 'Logout realizado com sucesso.'
        ), 200);
    }

    public function me()
    {
        $this->requireAuth();
        $this->json(array(
            'success' => true,
            'data' => $_SESSION['user']
        ), 200);
    }
}
