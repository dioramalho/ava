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
}
