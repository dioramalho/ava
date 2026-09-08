<?php

class Request
{
    private $body;

    public function __construct()
    {
        $this->body = $_POST;

        $rawInput = file_get_contents('php://input');
        $jsonInput = json_decode($rawInput, true);

        if (is_array($jsonInput)) {
            $this->body = array_merge($this->body, $jsonInput);
        }
    }

    public function method()
    {
        return isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
    }

    public function input($key, $defaultValue)
    {
        if (!isset($this->body[$key]) || is_array($this->body[$key])) {
            return $defaultValue;
        }

        return trim((string) $this->body[$key]);
    }

    public function query($key, $defaultValue)
    {
        return isset($_GET[$key]) ? trim($_GET[$key]) : $defaultValue;
    }

    public function file($key)
    {
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
    }

    /**
     * Normaliza input file simples ou arquivos[].
     *
     * @return array
     */
    public function files($key)
    {
        if (!isset($_FILES[$key])) {
            return array();
        }

        $bag = $_FILES[$key];

        if (!isset($bag['name']) || !is_array($bag['name'])) {
            if (empty($bag['name']) || (isset($bag['error']) && (int) $bag['error'] === UPLOAD_ERR_NO_FILE)) {
                return array();
            }

            return array($bag);
        }

        $list = array();
        $total = count($bag['name']);

        for ($i = 0; $i < $total; $i++) {
            if ((int) $bag['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $list[] = array(
                'name' => $bag['name'][$i],
                'type' => $bag['type'][$i],
                'tmp_name' => $bag['tmp_name'][$i],
                'error' => $bag['error'][$i],
                'size' => $bag['size'][$i]
            );
        }

        return $list;
    }

    public function all()
    {
        return $this->body;
    }

    public function route()
    {
        if (!empty($_GET['route'])) {
            return '/' . trim($_GET['route'], '/');
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';

        if ($scriptName !== '/' && strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }

        if ($path === false || $path === '') {
            return '/';
        }

        return '/' . trim($path, '/');
    }
}
