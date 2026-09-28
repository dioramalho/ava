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
}
