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
