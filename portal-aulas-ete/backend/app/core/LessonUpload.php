<?php

class LessonUpload
{
    /**
     * @param array $file Item de $_FILES normalizado
     * @param array $config config['app']
     * @return array
     */
    public static function store($file, $config)
    {
        if (!is_array($file) || empty($file['tmp_name'])) {
            throw new InvalidArgumentException('Arquivo inválido.');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Falha no envio do arquivo.');
        }

        $maxBytes = isset($config['max_upload_bytes']) ? (int) $config['max_upload_bytes'] : 10485760;
        if ((int) $file['size'] > $maxBytes) {
            throw new RuntimeException('Arquivo excede o tamanho máximo de 10 MB.');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = array('pdf', 'docx');
        if (!in_array($extension, $allowed, true)) {
            throw new RuntimeException('Apenas arquivos PDF ou DOCX são permitidos.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Origem do arquivo não é confiável.');
        }

        $uploadDir = $config['upload_dir'];
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Não foi possível preparar a pasta de uploads.');
        }

        $safeName = str_replace('.', '', uniqid('aula_', true)) . '.' . $extension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Não foi possível salvar o arquivo enviado.');
        }

        $original = basename($file['name']);
        $original = preg_replace('/[^\w\.\-\(\) ]+/u', '_', $original);

        return array(
            'nome_original' => $original,
            'caminho_arquivo' => 'backend/storage/uploads/' . $safeName,
            'extensao' => $extension
        );
    }

    public static function isYoutubeUrl($url)
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return false;
        }

        $host = strtolower($host);
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        return $host === 'youtube.com' || $host === 'youtu.be' || $host === 'm.youtube.com';
    }

    public static function youtubeId($url)
    {
        if (!self::isYoutubeUrl($url)) {
            return '';
        }

        $parts = parse_url($url);
        $host = isset($parts['host']) ? strtolower($parts['host']) : '';
        $path = isset($parts['path']) ? $parts['path'] : '';
        $id = '';

        if (strpos($host, 'youtu.be') !== false) {
            $id = trim($path, '/');
        } elseif (!empty($parts['query'])) {
            parse_str($parts['query'], $query);
            if (!empty($query['v'])) {
                $id = $query['v'];
            }
        }

        if ($id === '' && preg_match('#/(embed|shorts)/([A-Za-z0-9_-]+)#', $path, $matches)) {
            $id = $matches[2];
        }

        if (!preg_match('/^[A-Za-z0-9_-]{6,20}$/', $id)) {
            return '';
        }

        return $id;
    }

    public static function youtubeEmbedUrl($url)
    {
        $id = self::youtubeId($url);
        if ($id === '') {
            return '';
        }

        return 'https://www.youtube.com/embed/' . $id;
    }
}
