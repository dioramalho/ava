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

        if (!$user || sha1($senha) !== $user['senha']) {
            $this->json(array(
                'success' => false,
                'message' => 'Credenciais inválidas.'
            ), 401);
        }

        session_regenerate_id(true);
        $_SESSION['user'] = array(
            'id' => (int) $user['id'],
            'nome' => $user['nome'],
            'login' => $user['login'],
            'perfil' => $user['perfil']
        );

        $this->json(array(
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'data' => $_SESSION['user']
        ), 200);
    }

    public function logout()
    {
        $_SESSION = array();
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
