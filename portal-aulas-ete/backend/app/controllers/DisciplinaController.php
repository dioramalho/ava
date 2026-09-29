<?php

class DisciplinaController extends Controller
{
    public function index()
    {
        $this->requireProfessor();
        $model = new Disciplina();

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

        $model = new Disciplina();
        if ($model->findByCodigo($codigo, 0)) {
            $this->json(array(
                'success' => false,
                'message' => 'Já existe uma disciplina com este código.'
            ), 409);
        }

        $id = $model->create(array(
            'codigo' => $codigo,
            'titulo' => $titulo
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Disciplina cadastrada com sucesso.',
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

        $model = new Disciplina();
        if (!$model->findById($id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Disciplina não encontrada.'
            ), 404);
        }

        if ($model->findByCodigo($codigo, $id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Já existe uma disciplina com este código.'
            ), 409);
        }

        $model->update($id, array(
            'codigo' => $codigo,
            'titulo' => $titulo
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Disciplina atualizada com sucesso.'
        ), 200);
    }

    public function destroy(Request $request)
    {
        $this->requireProfessor();

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID da disciplina é obrigatório.'
            ), 422);
        }

        $model = new Disciplina();
        if (!$model->findById($id)) {
            $this->json(array(
                'success' => false,
                'message' => 'Disciplina não encontrada.'
            ), 404);
        }

        $aulaModel = new Aula();
        $totalAulas = $aulaModel->countByDisciplina($id);
        if ($totalAulas > 0) {
            $this->json(array(
                'success' => false,
                'message' => 'Não é possível excluir a disciplina: ela ainda tem ' . $totalAulas . ' aula(s) vinculada(s). Mova ou exclua essas aulas antes.'
            ), 409);
        }

        try {
            $model->delete($id);
        } catch (PDOException $exception) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível excluir a disciplina: ainda há aulas vinculadas.'
            ), 409);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Disciplina excluída com sucesso.'
        ), 200);
    }
}
