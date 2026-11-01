<?php

class AlunoPortalController extends Controller
{
    public function painel()
    {
        $this->requireAluno();

        $user = $_SESSION['user'];
        $turmaId = isset($user['turma_id']) ? (int) $user['turma_id'] : 0;
        $alunoId = isset($user['id']) ? (int) $user['id'] : 0;
        $aulaModel = new Aula();
        $trabalhoModel = new Trabalho();
        $pendentes = array();

        if ($turmaId > 0 && $alunoId > 0) {
            $trabalhoModel->sincronizarAluno($turmaId, $alunoId);
            $pendentes = $trabalhoModel->pendentesDoAluno($alunoId, $turmaId);
        }

        $this->json(array(
            'success' => true,
            'data' => array(
                'aluno' => $user,
                'disciplinas' => $aulaModel->disciplinasDaTurma($turmaId),
                'aulas_recentes' => $aulaModel->recentByTurma($turmaId, 5),
                'trabalhos_pendentes' => $pendentes
            )
        ), 200);
    }
}
