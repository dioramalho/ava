<?php

class AlunoController extends Controller
{
    public function index()
    {
        $this->requireProfessor();
        $model = new User();

        $this->json(array(
            'success' => true,
            'data' => $model->listAprovados()
        ), 200);
    }

    public function pendentes()
    {
        $this->requireProfessor();
        $model = new User();

        $this->json(array(
            'success' => true,
            'data' => $model->listPendentes()
        ), 200);
    }

    public function aprovar(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        $turmaId = (int) $request->input('turma_id', 0);

        if ($id <= 0 || $turmaId <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'Aluno e turma são obrigatórios.'
            ), 422);
        }

        $turmaModel = new Turma();
        if (!$turmaModel->findById($turmaId)) {
            $this->json(array(
                'success' => false,
                'message' => 'Turma inválida.'
            ), 422);
        }

        $userModel = new User();
        $aluno = $userModel->findById($id);
        if (!$aluno || $aluno['perfil'] !== 'aluno') {
            $this->json(array(
                'success' => false,
                'message' => 'Solicitação não encontrada.'
            ), 404);
        }

        if ($aluno['status'] !== 'pendente') {
            $this->json(array(
                'success' => false,
                'message' => 'Esta solicitação já foi analisada.'
            ), 409);
        }

        $userModel->approve($id, $turmaId);

        $this->json(array(
            'success' => true,
            'message' => 'Aluno aprovado e vinculado à turma.'
        ), 200);
    }

    public function recusar(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID do aluno é obrigatório.'
            ), 422);
        }

        $userModel = new User();
        $aluno = $userModel->findById($id);
        if (!$aluno || $aluno['perfil'] !== 'aluno' || $aluno['status'] !== 'pendente') {
            $this->json(array(
                'success' => false,
                'message' => 'Solicitação não encontrada.'
            ), 404);
        }

        $userModel->reject($id);

        $this->json(array(
            'success' => true,
            'message' => 'Solicitação recusada.'
        ), 200);
    }
}
