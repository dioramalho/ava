<?php

class AlunoController extends Controller
{
    const SENHA_PADRAO_ALUNO = '@ETE2626';

    public function __construct()
    {
        $formato = dirname(__DIR__) . '/core/AlunoFormato.php';
        if (!class_exists('AlunoFormato', false) && is_file($formato)) {
            require_once $formato;
        }
    }

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
        $email = AlunoFormato::email($request->input('email', ''));
        $celularInformado = $request->input('celular', '');
        $turmaId = (int) $request->input('turma_id', 0);
        $status = $request->input('status', '');

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

        $statusPermitidos = array('pendente', 'aprovado', 'recusado');
        if ($status !== '' && !in_array($status, $statusPermitidos, true)) {
            $this->json(array(
                'success' => false,
                'message' => 'Status inválido.'
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

        if ($status === '') {
            $status = $aluno['status'];
        }

        $celular = $this->celularParaSalvar($celularInformado, isset($aluno['celular']) ? $aluno['celular'] : '');
        if ($celular === false) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe o celular no formato (81)99794-1373 ou deixe em branco.'
            ), 422);
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
                'celular' => $celular,
                'turma_id' => $turmaId,
                'status' => $status
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

    public function resetSenha(Request $request)
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

        $hash = PasswordHelper::hash(self::SENHA_PADRAO_ALUNO);
        if (!$userModel->resetSenhaAluno($id, $hash)) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível redefinir a senha.'
            ), 500);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Senha redefinida. O aluno entra com @ETE2626.'
        ), 200);
    }

    /**
     * @return string|null|false null se vazio; false se o número mudou e não tem 11 dígitos
     */
    private function celularParaSalvar($informado, $atual)
    {
        $informado = trim((string) $informado);
        if ($informado === '') {
            return null;
        }

        $digitsNovo = AlunoFormato::digitos($informado);
        $digitsAtual = AlunoFormato::digitos($atual);

        if (strlen($digitsNovo) > 11) {
            $digitsNovo = substr($digitsNovo, 0, 11);
        }

        if (strlen($digitsNovo) === 11) {
            return AlunoFormato::celular($digitsNovo);
        }

        if ($digitsNovo === $digitsAtual) {
            $atual = trim((string) $atual);
            return $atual === '' ? null : $atual;
        }

        return false;
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
