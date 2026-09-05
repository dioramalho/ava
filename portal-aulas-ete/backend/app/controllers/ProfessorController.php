<?php

class ProfessorController extends Controller
{
    public function painel()
    {
        $this->requireProfessor();

        $turmaModel = new Turma();
        $disciplinaModel = new Disciplina();
        $aulaModel = new Aula();
        $userModel = new User();

        $this->json(array(
            'success' => true,
            'data' => array(
                'turmas' => $turmaModel->countAll(),
                'disciplinas' => $disciplinaModel->countAll(),
                'aulas' => $aulaModel->countAll(),
                'alunos_ativos' => $userModel->countAprovados(),
                'solicitacoes_pendentes' => $userModel->countPendentes(),
                'pendentes' => $userModel->listPendentes(),
                'aulas_recentes' => $aulaModel->recent(5),
                'lista_turmas' => $turmaModel->all()
            )
        ), 200);
    }
}
