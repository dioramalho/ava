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

    public function update(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        $nome = $request->input('nome', '');
        $email = strtolower($request->input('email', ''));
        $celular = $request->input('celular', '');
        $turmaId = (int) $request->input('turma_id', 0);

        if ($id <= 0 || $nome === '' || $email === '' || $turmaId <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'Nome, e-mail e turma são obrigatórios.'
            ), 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe um e-mail válido.'
            ), 422);
        }

        $userModel = new User();
        $aluno = $userModel->findById($id);
        if (!$aluno || $aluno['perfil'] !== 'aluno' || $aluno['status'] !== 'aprovado') {
            $this->json(array(
                'success' => false,
                'message' => 'Aluno não encontrado.'
            ), 404);
        }

        $turmaModel = new Turma();
        if (!$turmaModel->findById($turmaId)) {
            $this->json(array(
                'success' => false,
                'message' => 'Turma inválida.'
            ), 422);
        }

        if ($userModel->findByEmailOrLoginExcept($email, $id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Já existe uma conta com este e-mail.'
            ), 409);
        }

        // O login do aluno é o e-mail do cadastro; mantém os dois sincronizados.
        $emailAtual = strtolower((string) $aluno['email']);
        $login = strtolower((string) $aluno['login']) === $emailAtual ? $email : $aluno['login'];

        try {
            $userModel->updateAluno($id, array(
                'nome' => $nome,
                'login' => $login,
                'email' => $email,
                'celular' => $celular !== '' ? $celular : null,
                'turma_id' => $turmaId
            ));
        } catch (PDOException $exception) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível atualizar o aluno. Verifique se o e-mail já está em uso.'
            ), 409);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Dados do aluno atualizados com sucesso.'
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

        $userModel = new User();
        $aluno = $userModel->findById($id);
        if (!$aluno || $aluno['perfil'] !== 'aluno') {
            $this->json(array(
                'success' => false,
                'message' => 'Aluno não encontrado.'
            ), 404);
        }

        if (!$userModel->deleteAluno($id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível excluir o aluno.'
            ), 500);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Aluno excluído com sucesso.'
        ), 200);
    }
}
