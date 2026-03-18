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
        return isset($this->body[$key]) ? trim($this->body[$key]) : $defaultValue;
    }

    public function query($key, $defaultValue)
    {
        return isset($_GET[$key]) ? trim($_GET[$key]) : $defaultValue;
    }

    public function file($key)
    {
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
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
