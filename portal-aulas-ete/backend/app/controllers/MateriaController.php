<?php

class MateriaController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAuth();
        $model = new Materia();
        $turmaId = (int) $request->query('turma_id', 0);

        $this->json(array(
            'success' => true,
            'data' => $model->all($turmaId)
        ), 200);
    }

    public function store(Request $request)
    {
        $this->requireAuth();

        $turmaId = (int) $request->input('turma_id', 0);
        $nome = $request->input('nome', '');
        $descricao = $request->input('descricao', '');

        if ($turmaId <= 0 || $nome === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Turma e nome da matéria são obrigatórios.'
            ), 422);
        }

        $model = new Materia();
        $id = $model->create(array(
            'turma_id' => $turmaId,
            'nome' => $nome,
            'descricao' => $descricao
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Matéria cadastrada com sucesso.',
            'id' => (int) $id
        ), 201);
    }
}
