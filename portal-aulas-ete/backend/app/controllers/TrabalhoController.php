<?php

class TrabalhoController extends Controller
{
    public function index()
    {
        $this->requireProfessor();

        $model = new Trabalho();
        $this->json(array(
            'success' => true,
            'data' => $model->all()
        ), 200);
    }

    public function show(Request $request)
    {
        $this->requireProfessor();

        $trabalho = $this->trabalhoDoProfessor($request);
        $model = new Trabalho();
        $model->sincronizarTrabalho($trabalho);

        $this->json(array(
            'success' => true,
            'data' => array(
                'trabalho' => $this->enriquecer($trabalho),
                'alunos' => $model->participacoesDoTrabalho($trabalho['id'])
            )
        ), 200);
    }

    public function store(Request $request)
    {
        $this->requireProfessor();

        $titulo = $request->input('titulo', '');
        $descricao = $request->input('descricao', '');
        $dataEntrega = $request->input('data_entrega', '');
        $turmaId = (int) $request->input('turma_id', 0);
        $aulaId = (int) $request->input('aula_id', 0);
        $videoUrl = $request->input('video_url', '');

        if ($titulo === '' || $descricao === '' || $turmaId <= 0 || $aulaId <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'Título, descrição, turma e aula são obrigatórios.'
            ), 422);
        }

        if (strlen($titulo) > 180 || strlen($descricao) > 10000) {
            $this->json(array(
                'success' => false,
                'message' => 'Título ou descrição excede o tamanho permitido.'
            ), 422);
        }

        if (!$this->dataValida($dataEntrega)) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe uma data de entrega válida.'
            ), 422);
        }

        $aulaModel = new Aula();
        $aula = $aulaModel->findById($aulaId);
        if (!$aula || (int) $aula['turma_id'] !== $turmaId) {
            $this->json(array(
                'success' => false,
                'message' => 'A aula precisa pertencer à turma escolhida.'
            ), 422);
        }

        $arquivo = $request->file('arquivo');
        $temArquivo = is_array($arquivo) && !empty($arquivo['name']) && (int) $arquivo['error'] !== UPLOAD_ERR_NO_FILE;

        if ($temArquivo && $videoUrl !== '') {
            $this->json(array(
                'success' => false,
                'message' => 'Envie um PDF ou um link de vídeo, não os dois.'
            ), 422);
        }

        if ($videoUrl !== '' && !LessonUpload::isYoutubeUrl($videoUrl)) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe uma URL válida do YouTube.'
            ), 422);
        }

        $config = require dirname(dirname(__DIR__)) . '/config/config.php';
        $stored = null;

        if ($temArquivo) {
            try {
                $stored = LessonUpload::storeAs(
                    $arquivo,
                    $config['app'],
                    'trabalho_',
                    array('pdf'),
                    'O material do trabalho aceita apenas PDF.'
                );
            } catch (Exception $exception) {
                $this->json(array(
                    'success' => false,
                    'message' => $exception->getMessage()
                ), 422);
            }
        }

        $model = new Trabalho();
        $model->beginTransaction();
        $trabalhoId = 0;

        try {
            $trabalhoId = $model->create(array(
                'titulo' => $titulo,
                'descricao' => $descricao,
                'data_entrega' => $dataEntrega,
                'turma_id' => $turmaId,
                'aula_id' => $aulaId,
                'arquivo_nome' => $stored ? $stored['nome_original'] : null,
                'arquivo_caminho' => $stored ? $stored['caminho_arquivo'] : null,
                'arquivo_extensao' => $stored ? $stored['extensao'] : null,
                'video_url' => $videoUrl !== '' ? $videoUrl : null
            ));
            $trabalho = $model->findById($trabalhoId);
            $novos = $model->sincronizarTrabalho($trabalho);
            $model->commit();
        } catch (Exception $exception) {
            $model->rollBack();
            if ($stored) {
                LessonUpload::deleteStored($stored['caminho_arquivo'], $config['app']);
            }
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível publicar o trabalho.'
            ), 500);
        }

        $envio = $this->notificarAlunos(
            $novos,
            'Nova atividade: ' . $titulo,
            'Nova atividade',
            '<p>O professor publicou o trabalho <strong>' . $this->h($titulo) . '</strong>, ligado à aula ' . $this->h($aula['titulo']) . '.</p>'
            . '<p>Data de entrega: <strong>' . $this->h($this->formatarData($dataEntrega)) . '</strong>.</p>'
            . $this->paragrafoPortal($trabalhoId)
        );

        $this->json(array(
            'success' => true,
            'message' => $this->mensagemEnvio('Trabalho publicado.', $envio, count($novos)),
            'id' => $trabalhoId,
            'emails_enviados' => $envio['enviados'],
            'emails_falhas' => $envio['falhas']
        ), 201);
    }

    public function status(Request $request)
    {
        $this->requireProfessor();

        $participacaoId = (int) $request->input('trabalho_aluno_id', 0);
        $status = $request->input('status', '');
        $permitidos = array('pendente', 'entregue', 'entregue_atraso');

        if ($participacaoId <= 0 || !in_array($status, $permitidos, true)) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe o aluno e um status válido.'
            ), 422);
        }

        $model = new Trabalho();
        $participacao = $model->findParticipacao($participacaoId);
        if (!$participacao || !$model->findById($participacao['trabalho_id'])) {
            $this->json(array(
                'success' => false,
                'message' => 'Entrega não encontrada.'
            ), 404);
        }

        $autorId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        $model->beginTransaction();

        try {
            $model->atualizarStatus($participacaoId, $status);
            $model->addEvento($participacaoId, array(
                'tipo' => 'status',
                'autor_id' => $autorId > 0 ? $autorId : null,
                'autor_perfil' => 'professor',
                'texto' => 'Status alterado para ' . $this->rotuloStatus($status) . '.',
                'arquivo_nome' => null,
                'arquivo_caminho' => null,
                'arquivo_extensao' => null,
                'data_entrega' => null,
                'status' => $status
            ));
            $model->commit();
        } catch (Exception $exception) {
            $model->rollBack();
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível atualizar o status.'
            ), 500);
        }

        $this->json(array(
            'success' => true,
            'message' => 'Status atualizado.'
        ), 200);
    }

    public function feedback(Request $request)
    {
        $this->requireProfessor();

        $participacaoId = (int) $request->input('trabalho_aluno_id', 0);
        $texto = $request->input('texto', '');
        $dataEntrega = $request->input('data_entrega', '');

        if ($participacaoId <= 0 || $texto === '') {
            $this->json(array(
                'success' => false,
                'message' => 'Informe o aluno e o feedback.'
            ), 422);
        }

        if (strlen($texto) > 5000) {
            $this->json(array(
                'success' => false,
                'message' => 'O feedback excede o tamanho permitido.'
            ), 422);
        }

        if (!$this->dataValida($dataEntrega)) {
            $this->json(array(
                'success' => false,
                'message' => 'Informe a nova data de entrega.'
            ), 422);
        }

        $model = new Trabalho();
        $participacao = $model->findParticipacao($participacaoId);
        $trabalho = $participacao ? $model->findById($participacao['trabalho_id']) : null;
        if (!$participacao || !$trabalho) {
            $this->json(array(
                'success' => false,
                'message' => 'Entrega não encontrada.'
            ), 404);
        }

        $autorId = isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : 0;
        $model->beginTransaction();

        try {
            $model->reabrirEntrega($participacaoId, $dataEntrega);
            $model->addEvento($participacaoId, array(
                'tipo' => 'feedback',
                'autor_id' => $autorId > 0 ? $autorId : null,
                'autor_perfil' => 'professor',
                'texto' => $texto,
                'arquivo_nome' => null,
                'arquivo_caminho' => null,
                'arquivo_extensao' => null,
                'data_entrega' => $dataEntrega,
                'status' => 'pendente'
            ));
            $model->commit();
        } catch (Exception $exception) {
            $model->rollBack();
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível registrar o feedback.'
            ), 500);
        }

        $envio = $this->notificarAlunos(
            array(array(
                'nome' => $participacao['aluno_nome'],
                'email' => $participacao['aluno_email']
            )),
            'Correção solicitada: ' . $trabalho['titulo'],
            'Nova entrega',
            '<p>O professor pediu uma correção no trabalho <strong>' . $this->h($trabalho['titulo']) . '</strong>.</p>'
            . '<p>' . nl2br($this->h($texto)) . '</p>'
            . '<p>Nova data de entrega: <strong>' . $this->h($this->formatarData($dataEntrega)) . '</strong>.</p>'
            . $this->paragrafoPortal($trabalho['id'])
        );

        $this->json(array(
            'success' => true,
            'message' => $this->mensagemEnvio('Feedback registrado.', $envio, 1),
            'emails_enviados' => $envio['enviados'],
            'emails_falhas' => $envio['falhas']
        ), 200);
    }

    public function alunoIndex()
    {
        $this->requireAluno();

        $turmaId = $this->turmaDoAluno();
        $alunoId = (int) $_SESSION['user']['id'];
        $model = new Trabalho();

        if ($turmaId > 0) {
            $model->sincronizarAluno($turmaId, $alunoId);
        }

        $this->json(array(
            'success' => true,
            'data' => $turmaId > 0 ? $model->listarDoAluno($alunoId, $turmaId) : array()
        ), 200);
    }

    public function alunoShow(Request $request)
    {
        $this->requireAluno();

        $contexto = $this->contextoDoAluno($request);
        $this->json(array(
            'success' => true,
            'data' => $contexto
        ), 200);
    }

    public function entregar(Request $request)
    {
        $this->requireAluno();

        $observacoes = $request->input('observacoes', '');
        if (strlen($observacoes) > 5000) {
            $this->json(array(
                'success' => false,
                'message' => 'As observações excedem o tamanho permitido.'
            ), 422);
        }

        $arquivo = $request->file('arquivo');
        $temArquivo = is_array($arquivo) && !empty($arquivo['name']) && (int) $arquivo['error'] !== UPLOAD_ERR_NO_FILE;

        if ($observacoes === '' && !$temArquivo) {
            $this->json(array(
                'success' => false,
                'message' => 'Escreva uma observação ou anexe um arquivo Word ou PDF.'
            ), 422);
        }

        $contexto = $this->contextoDoAluno($request);
        $participacao = $contexto['participacao'];

        if ($participacao['status'] !== 'pendente') {
            $this->json(array(
                'success' => false,
                'message' => 'Esta entrega já foi registrada. Aguarde o feedback do professor para enviar de novo.'
            ), 422);
        }

        $config = require dirname(dirname(__DIR__)) . '/config/config.php';
        $stored = null;

        if ($temArquivo) {
            try {
                $stored = LessonUpload::storeAs(
                    $arquivo,
                    $config['app'],
                    'entrega_',
                    array('pdf', 'doc', 'docx'),
                    'A resposta aceita apenas Word (DOC ou DOCX) ou PDF.'
                );
            } catch (Exception $exception) {
                $this->json(array(
                    'success' => false,
                    'message' => $exception->getMessage()
                ), 422);
            }
        }

        $status = $this->statusPelaData($participacao['data_entrega_vigente']);
        $model = new Trabalho();
        $autorId = (int) $_SESSION['user']['id'];
        $model->beginTransaction();

        try {
            $model->atualizarStatus($participacao['id'], $status);
            $model->addEvento($participacao['id'], array(
                'tipo' => 'entrega',
                'autor_id' => $autorId,
                'autor_perfil' => 'aluno',
                'texto' => $observacoes !== '' ? $observacoes : null,
                'arquivo_nome' => $stored ? $stored['nome_original'] : null,
                'arquivo_caminho' => $stored ? $stored['caminho_arquivo'] : null,
                'arquivo_extensao' => $stored ? $stored['extensao'] : null,
                'data_entrega' => null,
                'status' => $status
            ));
            $model->commit();
        } catch (Exception $exception) {
            $model->rollBack();
            if ($stored) {
                LessonUpload::deleteStored($stored['caminho_arquivo'], $config['app']);
            }
            $this->json(array(
                'success' => false,
                'message' => 'Não foi possível registrar a entrega.'
            ), 500);
        }

        $this->json(array(
            'success' => true,
            'message' => $status === 'entregue_atraso'
                ? 'Entrega registrada com atraso.'
                : 'Entrega registrada.',
            'status' => $status
        ), 200);
    }

    private function trabalhoDoProfessor(Request $request)
    {
        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            $id = (int) $request->input('id', 0);
        }

        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID do trabalho é obrigatório.'
            ), 422);
        }

        $model = new Trabalho();
        $trabalho = $model->findById($id);
        if (!$trabalho) {
            $this->json(array(
                'success' => false,
                'message' => 'Trabalho não encontrado.'
            ), 404);
        }

        return $trabalho;
    }

    private function contextoDoAluno(Request $request)
    {
        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            $id = (int) $request->input('trabalho_id', 0);
        }
        if ($id <= 0) {
            $id = (int) $request->input('id', 0);
        }

        if ($id <= 0) {
            $this->json(array(
                'success' => false,
                'message' => 'ID do trabalho é obrigatório.'
            ), 422);
        }

        $model = new Trabalho();
        $trabalho = $model->findById($id);
        $turmaId = $this->turmaDoAluno();
        $alunoId = (int) $_SESSION['user']['id'];

        if (!$trabalho || $turmaId <= 0 || (int) $trabalho['turma_id'] !== $turmaId) {
            $this->json(array(
                'success' => false,
                'message' => 'Acesso não autorizado.'
            ), 403);
        }

        $model->sincronizarAluno($turmaId, $alunoId);
        $participacoes = $model->participacoesDoTrabalho($trabalho['id']);
        $participacao = null;

        foreach ($participacoes as $linha) {
            if ((int) $linha['aluno_id'] === $alunoId) {
                $participacao = $linha;
                break;
            }
        }

        if (!$participacao) {
            $this->json(array(
                'success' => false,
                'message' => 'Acesso não autorizado.'
            ), 403);
        }

        return array(
            'trabalho' => $this->enriquecer($trabalho),
            'participacao' => $participacao
        );
    }

    private function turmaDoAluno()
    {
        return isset($_SESSION['user']['turma_id']) ? (int) $_SESSION['user']['turma_id'] : 0;
    }

    private function enriquecer($trabalho)
    {
        $trabalho['embed_url'] = '';
        if (!empty($trabalho['video_url'])) {
            $trabalho['embed_url'] = LessonUpload::youtubeEmbedUrl($trabalho['video_url']);
        }

        return $trabalho;
    }

    private function statusPelaData($dataVigente)
    {
        $hoje = date('Y-m-d');
        $limite = substr((string) $dataVigente, 0, 10);

        return $hoje <= $limite ? 'entregue' : 'entregue_atraso';
    }

    private function dataValida($valor)
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $valor)) {
            return false;
        }

        $partes = explode('-', $valor);

        return checkdate((int) $partes[1], (int) $partes[2], (int) $partes[0]);
    }

    private function formatarData($valor)
    {
        $data = DateTime::createFromFormat('Y-m-d', substr((string) $valor, 0, 10));

        return $data ? $data->format('d/m/Y') : (string) $valor;
    }

    private function rotuloStatus($status)
    {
        if ($status === 'entregue') {
            return 'entregue';
        }
        if ($status === 'entregue_atraso') {
            return 'entregue com atraso';
        }

        return 'pendente';
    }

    private function h($valor)
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }

    private function paragrafoPortal($trabalhoId)
    {
        $link = $this->linkTrabalho($trabalhoId);
        if ($link === '') {
            return '<p>Entre no portal e abra a área de trabalhos para ver o enunciado.</p>';
        }

        return '<p>Abra o trabalho no portal: <a href="' . $this->h($link) . '">' . $this->h($link) . '</a></p>';
    }

    private function linkTrabalho($trabalhoId)
    {
        $host = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
        if ($host === '') {
            return '';
        }

        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';

        $script = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string) $_SERVER['SCRIPT_NAME']) : '';
        $portal = dirname(dirname(dirname($script)));
        if ($portal === '/' || $portal === '\\' || $portal === '.') {
            $portal = '/portal-aulas-ete';
        }

        return $scheme . '://' . $host . $portal . '/aluno-trabalho.html?id=' . (int) $trabalhoId;
    }

    private function notificarAlunos($alunos, $assunto, $titulo, $conteudo)
    {
        $enviados = 0;
        $falhas = 0;
        $mailer = new Mailer();
        $html = EmailTemplate::render(array(
            'title' => $assunto,
            'preheader' => $assunto,
            'heading' => $titulo,
            'content' => $conteudo
        ));
        $texto = trim(strip_tags(str_replace(array('<br>', '<br/>', '<br />'), "\n", $conteudo)));

        foreach ($alunos as $aluno) {
            $email = isset($aluno['email']) ? trim((string) $aluno['email']) : '';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $falhas++;
                continue;
            }

            try {
                $mailer->send($email, $assunto, $html, $texto);
                $enviados++;
            } catch (Exception $exception) {
                error_log('Portal trabalho e-mail: ' . $exception->getMessage());
                $falhas++;
            }
        }

        return array(
            'enviados' => $enviados,
            'falhas' => $falhas
        );
    }

    private function mensagemEnvio($base, $envio, $destinatarios)
    {
        if ($destinatarios <= 0) {
            return $base . ' Nenhum aluno aprovado nesta turma para notificar.';
        }

        if ($envio['falhas'] > 0 && $envio['enviados'] === 0) {
            return $base . ' Não foi possível enviar o e-mail.';
        }

        if ($envio['falhas'] > 0) {
            return $base . ' ' . $envio['falhas'] . ' e-mail(s) não puderam ser enviados.';
        }

        return $base . ' Alunos notificados por e-mail.';
    }
}
