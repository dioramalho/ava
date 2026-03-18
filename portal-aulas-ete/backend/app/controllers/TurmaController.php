<?php

class TurmaController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $model = new Turma();

        $this->json(array(
            'success' => true,
            'data' => $model->all()
        ), 200);
    }

    public function store(Request $request)
    {
        $this->requireAuth();

        $nome = $request->input('nome', '');
        $anoLetivo = $request->input('ano_letivo', '');
        $turno = $request->input('turno', '');
        $descricao = $request->input('descricao', '');

        if ($nome === '' || $anoLetivo === '' || $turno === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Nome, ano letivo e turno são obrigatórios.'
            ), 422);
        }

        $model = new Turma();
        $id = $model->create(array(
            'nome' => $nome,
            'ano_letivo' => $anoLetivo,
            'turno' => $turno,
            'descricao' => $descricao
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Turma cadastrada com sucesso.',
            'id' => (int) $id
        ), 201);
    }
}
