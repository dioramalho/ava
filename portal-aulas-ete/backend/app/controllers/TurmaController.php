<?php

class TurmaController extends Controller
{
    public function index()
    {
        $this->requireProfessor();
        $model = new Turma();

        $this->json(array(
            'success' => true,
            'data' => $model->all()
        ), 200);
    }

    public function store(Request $request)
    {
        $this->requireProfessor();

        $codigo = strtoupper($request->input('codigo', ''));
        $titulo = $request->input('titulo', '');

        if ($codigo === '' || $titulo === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Código e título são obrigatórios.'
            ), 422);
        }

        $model = new Turma();
        if ($model->findByCodigo($codigo, 0)) {
            $this->json(array(
                'success' => false,
                'message' => 'Já existe uma turma com este código.'
            ), 409);
        }

        $id = $model->create(array(
            'codigo' => $codigo,
            'titulo' => $titulo
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Turma cadastrada com sucesso.',
            'id' => (int) $id
        ), 201);
    }

    public function update(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        $codigo = strtoupper($request->input('codigo', ''));
        $titulo = $request->input('titulo', '');

        if ($id <= 0 || $codigo === '' || $titulo === '') {
            $this->json(array(
                'success' => false,
                'message' => 'ID, código e título são obrigatórios.'
            ), 422);
        }

        $model = new Turma();
        if (!$model->findById($id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Turma não encontrada.'
            ), 404);
        }

        if ($model->findByCodigo($codigo, $id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Já existe uma turma com este código.'
            ), 409);
        }

        $model->update($id, array(
            'codigo' => $codigo,
            'titulo' => $titulo
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Turma atualizada com sucesso.'
        ), 200);
    }

    public function destroy(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID da turma é obrigatório.'
            ), 422);
        }

        $model = new Turma();
        if (!$model->findById($id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Turma não encontrada.'
            ), 404);
        }

        $aulaModel = new Aula();
        $userModel = new User();
        $totalAulas = $aulaModel->countByTurma($id);
        $totalAlunos = $userModel->countAlunosByTurma($id);

        if ($totalAulas > 0 || $totalAlunos > 0) {
            $vinculos = array();
            if ($totalAlunos > 0) {
                $vinculos[] = $totalAlunos . ' aluno(s)';
            }
            if ($totalAulas > 0) {
                $vinculos[] = $totalAulas . ' aula(s)';
            }

            $this->json(array(
                'success' => false,
                'message' => 'Não é possível excluir a turma: ela ainda tem ' . implode(' e ', $vinculos) . ' vinculado(s). Mova ou exclua esses registros antes.'
            ), 409);
        }

        try {
            $model->delete($id);
        } catch (PDOException $exception) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível excluir a turma: ainda há registros vinculados.'
            ), 409);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Turma excluída com sucesso.'
        ), 200);
    }
}
