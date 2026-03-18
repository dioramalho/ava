<?php

class App
{
    private $request;
    private $routes;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->routes = array(
            'GET' => array(),
            'POST' => array()
        );
        $this->registerRoutes();
    }

    public function run()
    {
        $method = $this->request->method();
        $route = $this->request->route();

        if ($route === '/') {
            Response::json(array(
                'success' => true,
                'message' => 'API Portal de Aulas - TDS',
                'version' => '1.0.0'
            ), 200);
        }

        if (!isset($this->routes[$method][$route])) {
            Response::json(array(
                'success' => false,
                'message' => 'Rota não encontrada.'
            ), 404);
        }

        $handler = $this->routes[$method][$route];
        $controller = new $handler[0]();
        $action = $handler[1];

        call_user_func(array($controller, $action), $this->request);
    }

    private function registerRoutes()
    {
        $this->get('/health', 'AuthController', 'health');
        $this->post('/auth/login', 'AuthController', 'login');
        $this->post('/auth/logout', 'AuthController', 'logout');
        $this->get('/auth/me', 'AuthController', 'me');

        $this->get('/turmas', 'TurmaController', 'index');
        $this->post('/turmas', 'TurmaController', 'store');

        $this->get('/alunos', 'AlunoController', 'index');
        $this->post('/alunos', 'AlunoController', 'store');

        $this->get('/materias', 'MateriaController', 'index');
        $this->post('/materias', 'MateriaController', 'store');

        $this->get('/recados', 'RecadoController', 'index');
        $this->post('/recados', 'RecadoController', 'store');

        $this->get('/documentos', 'DocumentoController', 'index');
        $this->post('/documentos', 'DocumentoController', 'store');
    }

    private function get($route, $controller, $action)
    {
        $this->routes['GET'][$route] = array($controller, $action);
    }

    private function post($route, $controller, $action)
    {
        $this->routes['POST'][$route] = array($controller, $action);
    }
}
