<?php

class AlunoPortalController extends Controller
{
    public function painel()
    {
        $this->requireAluno();

        $user = $_SESSION['user'];
        $turmaId = isset($user['turma_id']) ? (int) $user['turma_id'] : 0;
        $aulaModel = new Aula();

        $this->json(array(
            'success' => true,
            'data' => array(
                'aluno' => $user,
                'disciplinas' => $aulaModel->disciplinasDaTurma($turmaId),
                'aulas_recentes' => $aulaModel->recentByTurma($turmaId, 5)
            )
        ), 200);
    }
}
