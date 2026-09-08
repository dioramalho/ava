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
        $this->post('/auth/cadastro-aluno', 'AuthController', 'cadastroAluno');

        $this->get('/professor/painel', 'ProfessorController', 'painel');
        $this->get('/aluno/painel', 'AlunoPortalController', 'painel');

        $this->get('/turmas', 'TurmaController', 'index');
        $this->post('/turmas', 'TurmaController', 'store');
        $this->post('/turmas/update', 'TurmaController', 'update');

        $this->get('/disciplinas', 'DisciplinaController', 'index');
        $this->post('/disciplinas', 'DisciplinaController', 'store');

        $this->get('/aulas', 'AulaController', 'index');
        $this->get('/aulas/detalhe', 'AulaController', 'show');
        $this->post('/aulas', 'AulaController', 'store');
        $this->post('/aulas/update', 'AulaController', 'update');

        $this->get('/alunos', 'AlunoController', 'index');
        $this->get('/alunos/pendentes', 'AlunoController', 'pendentes');
        $this->post('/alunos/aprovar', 'AlunoController', 'aprovar');
        $this->post('/alunos/recusar', 'AlunoController', 'recusar');
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
