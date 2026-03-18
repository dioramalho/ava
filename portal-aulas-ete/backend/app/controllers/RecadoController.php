<?php

class RecadoController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAuth();
        $model = new Recado();
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
        $titulo = $request->input('titulo', '');
        $mensagem = $request->input('mensagem', '');

        if ($turmaId <= 0 || $titulo === '' || $mensagem === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Turma, título e mensagem são obrigatórios.'
            ), 422);
        }

        $model = new Recado();
        $id = $model->create(array(
            'turma_id' => $turmaId,
            'titulo' => $titulo,
            'mensagem' => $mensagem
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Recado publicado com sucesso.',
            'id' => (int) $id
        ), 201);
    }
}
