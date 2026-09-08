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
}
