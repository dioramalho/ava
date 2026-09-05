<?php

class AulaController extends Controller
{
    public function index(Request $request)
    {
        $this->requireAuth();

        $user = $_SESSION['user'];
        $model = new Aula();
        $turmaId = (int) $request->query('turma_id', 0);
        $disciplinaId = (int) $request->query('disciplina_id', 0);

        if ($user['perfil'] === 'aluno') {
            $turmaId = isset($user['turma_id']) ? (int) $user['turma_id'] : 0;
            if ($turmaId <= 0) {
                $this->json(array(
                    'success' => true,
                    'data' => array()
                ), 200);
            }
        }

        $data = $model->all($turmaId, $disciplinaId);

        $this->json(array(
            'success' => true,
            'data' => $data
        ), 200);
    }

    public function show(Request $request)
    {
        $this->requireAuth();

        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID da aula é obrigatório.'
            ), 422);
        }

        $model = new Aula();
        $aula = $model->findById($id);
        if (!$aula) {
            $this->json(array(
                'success' => false,
                'message' => 'Aula não encontrada.'
            ), 404);
        }

        $user = $_SESSION['user'];
        $turmaSessao = isset($user['turma_id']) ? (int) $user['turma_id'] : 0;
        if ($user['perfil'] === 'aluno' && (int) $aula['turma_id'] !== $turmaSessao) {
            $this->json(array(
                'success' => false,
                'message' => 'Acesso não autorizado.'
            ), 403);
        }

        $this->json(array(
            'success' => true,
            'data' => $aula
        ), 200);
    }

    public function store(Request $request)
    {
        $this->persist($request, 0);
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID da aula é obrigatório.'
            ), 422);
        }

        $this->persist($request, $id);
    }

    private function persist(Request $request, $aulaId)
    {
        $this->requireProfessor();

        $titulo = $request->input('titulo', '');
        $turmaId = (int) $request->input('turma_id', 0);
        $disciplinaId = (int) $request->input('disciplina_id', 0);
        $videosRaw = $request->input('videos', '[]');

        if ($titulo === '' || $turmaId <= 0 || $disciplinaId <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'Título, turma e disciplina são obrigatórios.'
            ), 422);
        }

        $turmaModel = new Turma();
        $disciplinaModel = new Disciplina();
        if (!$turmaModel->findById($turmaId) || !$disciplinaModel->findById($disciplinaId)) {
            $this->json(array(
                'success' => false,
                'message' => 'Turma ou disciplina inválida.'
            ), 422);
        }

        $videos = json_decode($videosRaw, true);
        if (!is_array($videos)) {
            $videos = array();
        }

        $urls = array();
        foreach ($videos as $item) {
            $url = is_array($item) && isset($item['url']) ? trim($item['url']) : trim((string) $item);
            if ($url === '') {
                continue;
            }
            if (!LessonUpload::isYoutubeUrl($url)) {
                $this->json(array(
                    'success' => false,
                    'message' => 'Informe apenas URLs válidas do YouTube.'
                ), 422);
            }
            $urls[] = $url;
        }

        $config = require dirname(dirname(__DIR__)) . '/config/config.php';
        $files = $request->files('arquivos');
        if (!$files) {
            $files = $request->files('arquivos[]');
        }
        $storedFiles = array();

        try {
            foreach ($files as $file) {
                $storedFiles[] = LessonUpload::store($file, $config['app']);
            }
        } catch (Exception $exception) {
            $this->json(array(
                'success' => false,
                'message' => $exception->getMessage()
            ), 422);
        }

        $aulaModel = new Aula();

        if ($aulaId > 0 && !$aulaModel->findById($aulaId)) {
            $this->json(array(
                'success' => false,
                'message' => 'Aula não encontrada.'
            ), 404);
        }

        $aulaModel->beginTransaction();

        try {
            if ($aulaId > 0) {
                $aulaModel->update($aulaId, array(
                    'titulo' => $titulo,
                    'turma_id' => $turmaId,
                    'disciplina_id' => $disciplinaId
                ));
                $aulaModel->deleteVideos($aulaId);
            } else {
                $aulaId = (int) $aulaModel->create(array(
                    'titulo' => $titulo,
                    'turma_id' => $turmaId,
                    'disciplina_id' => $disciplinaId
                ));
            }

            foreach ($urls as $ordem => $url) {
                $aulaModel->addVideo($aulaId, $url, $ordem);
            }

            foreach ($storedFiles as $stored) {
                $stored['aula_id'] = $aulaId;
                $aulaModel->addArquivo($stored);
            }

            $aulaModel->commit();
        } catch (Exception $exception) {
            $aulaModel->rollBack();
            $this->json(array(
                'success' => false,
                'message' => $aulaId > 0 ? 'Não foi possível atualizar a aula.' : 'Não foi possível publicar a aula.'
            ), 500);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Aula publicada com sucesso.',
            'id' => (int) $aulaId
        ), $aulaId && $request->input('id', '') !== '' ? 200 : 201);
    }
}
