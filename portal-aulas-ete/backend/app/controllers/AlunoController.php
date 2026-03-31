<?php

class AlunoController extends Controller
{
    public function index()
    {
        $this->requireProfessor();
        $model = new Aluno();

        $this->json(array(
            'success' => true,
            'data' => $model->all()
        ), 200);
    }

    public function store(Request $request)
    {
        $this->requireProfessor();

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

        try {
            $id = $model->create(array(
                'turma_id' => $turmaId,
                'nome' => $nome,
                'email' => $email,
                'matricula' => $matricula
            ));
        } catch (PDOException $exception) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível cadastrar o aluno. Verifique se a matrícula já existe.'
            ), 409);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Aluno cadastrado com sucesso.',
            'id' => (int) $id
        ), 201);
    }

    public function update(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        $turmaId = (int) $request->input('turma_id', 0);
        $nome = $request->input('nome', '');
        $email = $request->input('email', '');
        $matricula = $request->input('matricula', '');

        if ($id <= 0 || $turmaId <= 0 || $nome === '' || $email === '' || $matricula === '') {
            $this->json(array(
                'success' => false,
                'message' => 'ID, turma, nome, e-mail e matrícula são obrigatórios.'
            ), 422);
        }

        $model = new Aluno();
        if (!$model->findById($id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Aluno não encontrado.'
            ), 404);
        }

        try {
            $model->update($id, array(
                'turma_id' => $turmaId,
                'nome' => $nome,
                'email' => $email,
                'matricula' => $matricula
            ));
        } catch (PDOException $exception) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível atualizar o aluno. Verifique se a matrícula já existe.'
            ), 409);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Aluno atualizado com sucesso.'
        ), 200);
    }

    public function destroy(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID do aluno é obrigatório.'
            ), 422);
        }

        $model = new Aluno();
        if (!$model->findById($id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Aluno não encontrado.'
            ), 404);
        }

        $model->delete($id);

        $this->json(array(
            'success' => true,
            'message' => 'Aluno excluído com sucesso.'
        ), 200);
    }
}
