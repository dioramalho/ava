<?php

class AlunoController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $model = new Aluno();

        $this->json(array(
            'success' => true,
            'data' => $model->all()
        ), 200);
    }

    public function store(Request $request)
    {
        $this->requireAuth();

        $turmaId = (int) $request->input('turma_id', 0);
        $nome = $request->input('nome', '');
        $email = $request->input('email', '');
        $matricula = $request->input('matricula', '');

        if ($turmaId <= 0 || $nome === '' || $email === '' || $matricula === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Turma, nome, e-mail e matrícula são obrigatórios.'
            ), 422);
        }

        $model = new Aluno();
        $id = $model->create(array(
            'turma_id' => $turmaId,
            'nome' => $nome,
            'email' => $email,
            'matricula' => $matricula
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Aluno cadastrado com sucesso.',
            'id' => (int) $id
        ), 201);
    }
}
