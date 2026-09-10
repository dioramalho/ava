<?php

class Controller
{
    protected function json($data, $statusCode)
    {
        Response::json($data, $statusCode);
    }

    protected function requireAuth()
    {
        if (empty($_SESSION['user'])) {
            $this->json(array(
                'success' => false,
                'message' => 'Acesso não autorizado.'
            ), 401);
        }

        $this->refreshSessionUser();
    }

    protected function refreshSessionUser()
    {
        $id = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        if ($id <= 0) {
            return;
        }

        $userModel = new User();
        $fresh = $userModel->findByIdWithTurma($id);
        if (!$fresh) {
            return;
        }

        $_SESSION['user'] = $userModel->toSessionUser($fresh);
    }

    protected function requireProfessor()
    {
        $this->requireAuth();

        $perfil = isset($_SESSION['user']['perfil']) ? $_SESSION['user']['perfil'] : '';
        if ($perfil !== 'professor') {
            $this->json(array(
                'success' => false,
                'message' => 'Acesso permitido apenas para professor.'
            ), 403);
        }
    }

    protected function requireAluno()
    {
        $this->requireAuth();

        $perfil = isset($_SESSION['user']['perfil']) ? $_SESSION['user']['perfil'] : '';
        if ($perfil !== 'aluno') {
            $this->json(array(
                'success' => false,
                'message' => 'Acesso permitido apenas para aluno.'
            ), 403);
        }
    }
}
