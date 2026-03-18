<?php

class DocumentoController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAuth();
        $model = new Documento();
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
        $materiaId = (int) $request->input('materia_id', 0);
        $titulo = $request->input('titulo', '');
        $descricao = $request->input('descricao', '');
        $arquivo = $request->file('arquivo');

        if ($turmaId <= 0 || $materiaId <= 0 || $titulo === '' || !$arquivo) {
            $this->json(array(
                'success' => false,
                'message' => 'Turma, matéria, título e arquivo PDF são obrigatórios.'
            ), 422);
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $this->json(array(
                'success' => false,
                'message' => 'Falha no upload do arquivo.'
            ), 422);
        }

        $extension = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            $this->json(array(
                'success' => false,
                'message' => 'Apenas arquivos PDF são permitidos.'
            ), 422);
        }

        $config = require dirname(dirname(__DIR__)) . '/config/config.php';
        $uploadDir = $config['app']['upload_dir'];
        $safeFileName = uniqid('pdf_', true) . '.pdf';
        $destination = $uploadDir . '/' . $safeFileName;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        if (!move_uploaded_file($arquivo['tmp_name'], $destination)) {
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível salvar o arquivo enviado.'
            ), 500);
        }

        $model = new Documento();
        $id = $model->create(array(
            'turma_id' => $turmaId,
            'materia_id' => $materiaId,
            'titulo' => $titulo,
            'nome_arquivo' => $arquivo['name'],
            'caminho_arquivo' => 'backend/storage/uploads/' . $safeFileName,
            'descricao' => $descricao
        ));

        $this->json(array(
            'success' => true,
            'message' => 'Documento publicado com sucesso.',
            'id' => (int) $id
        ), 201);
    }
}
