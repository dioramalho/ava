# Backend PHP — Portal de Aulas TDS

API JSON em **PHP 5.6**, **PDO**, **POO** e **MVC enxuto**, sem framework. O Composer cuida do autoload (`classmap`); se `vendor/` não existir, o `Autoloader.php` entra como fallback.

Documentação do repositório: [`../../README.md`](../../README.md).  
Documentação do front: [`../README.md`](../README.md).

---

## Sumário

1. [Estrutura](#estrutura)
2. [Requisitos](#requisitos)
3. [Configuração](#configuração)
4. [Ponto de entrada e roteamento](#ponto-de-entrada-e-roteamento)
5. [Camadas MVC](#camadas-mvc)
6. [Autenticação](#autenticação)
7. [Referência da API](#referência-da-api)
8. [Upload de aulas](#upload-de-aulas)
9. [Códigos HTTP](#códigos-http)
10. [Login padrão](#login-padrão)
11. [Limitações conhecidas](#limitações-conhecidas)
12. [Teste de impacto do Composer](#teste-de-impacto-do-composer)
13. [Como testar a API rapidamente](#como-testar-a-api-rapidamente)

---

## Estrutura

```text
backend/
├── app/
│   ├── controllers/     # Auth, Turma, Disciplina, Aula, Aluno, Professor, AlunoPortal
│   ├── core/            # App, Request, Response, Database, Controller, BaseModel, Autoloader, LessonUpload, PasswordHelper
│   └── models/
├── config/
│   ├── config.php
│   └── sqlite_schema.sql
├── public/
│   ├── index.php        # Front controller (Composer ou Autoloader)
│   └── .htaccess
├── scripts/
│   └── composer_impact_check.php
├── storage/
│   └── uploads/         # PDF/DOCX das aulas
├── composer.json
├── composer.lock
└── README.md
```

`vendor/` é gerado por `composer install` e não é versionado.

---

## Requisitos

- PHP **5.6+** (ambiente de referência: XAMPP 5.6.40)
- Extensão PDO + driver MySQL e/ou SQLite
- Apache com `mod_rewrite` (opcional) **ou** `php -S`
- Sessões PHP habilitadas
- [Composer](https://getcomposer.org/) 2 para instalar dependências (requer PHP 7.2.5+ só para rodar o `composer`; o código da API permanece compatível com 5.6)

```bash
cd portal-aulas-ete/backend
composer install
```

---

## Configuração

Arquivo: `config/config.php`

```php
return array(
    'db' => array(
        'host' => 'localhost',
        'port' => '3306',
        'dbname' => 'portal_aulas_ete',
        'charset' => 'utf8mb4',
        'username' => 'root',
        'password' => ''
    ),
    'app' => array(
        'base_path' => dirname(__DIR__),
        'upload_dir' => dirname(__DIR__) . '/storage/uploads',
        'upload_url' => '/portal-aulas-ete/backend/storage/uploads'
    )
);
```

### MySQL / MariaDB

1. Importe `../../portal_aulas_ete.sql`.
2. Ajuste host/usuário/senha se necessário.
3. Garanta que o PDO MySQL esteja ativo.

### SQLite

- Schema: `config/sqlite_schema.sql`
- Arquivo: `storage/database.sqlite` (criado sob demanda se o driver sqlite estiver corretamente configurado em `Database`)
- Na primeira criação, o schema + seed são aplicados automaticamente

### Variáveis de ambiente (pretendidas)

| Variável | Uso |
|----------|-----|
| `PORTAL_DB_DRIVER` | `sqlite` \| `mysql` |
| `PORTAL_DB_HOST` | Host |
| `PORTAL_DB_PORT` | Porta |
| `PORTAL_DB_NAME` | Database |
| `PORTAL_DB_USER` | Usuário |
| `PORTAL_DB_PASSWORD` | Senha |

> Hoje `config.php` lê `PORTAL_DB_DRIVER`, mas o valor **não é incluído** no array `db` retornado. `Database` então assume `mysql` por padrão. Para SQLite de fato, é necessário passar `driver` e `database` (caminho do `.sqlite`) em `config['db']`.

---

## Ponto de entrada e roteamento

**Arquivo:** `public/index.php`

1. `session_start()`
2. Headers CORS (`*`, métodos GET/POST/OPTIONS)
3. Autoload das classes (`vendor/autoload.php` ou `Autoloader.php`)
4. `Request` + `App::run()`

**URL típica (front):**

```text
backend/public/index.php?route=/auth/login
```

Rotas registradas em `app/core/App.php` apenas para **GET** e **POST**.

---

## Camadas MVC

| Camada | Responsabilidade |
|--------|------------------|
| `App` | Tabela de rotas e despacho |
| `Request` | Método, route, query, body JSON/form, files |
| `Response` | JSON + status HTTP |
| `Controller` | `json()`, `requireAuth()`, `requireProfessor()`, `requireAluno()` |
| `*Controller` | Regras de cada recurso |
| `BaseModel` / models | Acesso PDO às tabelas |
| `Database` | Singleton PDO (MySQL ou SQLite) |

---

## Autenticação

| Rota | Comportamento |
|------|----------------|
| `POST /auth/login` | Valida `login`/`senha` via `PasswordHelper`, grava `$_SESSION['user']` |
| `GET /auth/me` | Exige sessão; devolve usuário |
| `POST /auth/logout` | Limpa e destrói sessão |
| `POST /auth/cadastro-aluno` | Cria `usuarios` com `perfil=aluno` e `status=pendente` (sem sessão) |

Sessão típica:

```json
{
  "id": 1,
  "nome": "Professor Responsável",
  "login": "professor",
  "perfil": "professor"
}
```

Helpers:

- `requireAuth()` → 401 se sem sessão
- `requireProfessor()` → 403 se `perfil !== 'professor'`
- `requireAluno()` → 403 se `perfil !== 'aluno'`

---

## Referência da API

Base: `backend/public/index.php?route=`

Body JSON: `Content-Type: application/json`  
Cookies: necessários (`credentials: 'same-origin'` no front)

### Sistema

#### `GET /`

```json
{ "success": true, "message": "API Portal de Aulas - TDS", "version": "1.0.0" }
```

#### `GET /health`

```json
{ "success": true, "message": "Backend online." }
```

### Auth

#### `POST /auth/login`

**Body:**

```json
{ "login": "professor", "senha": "1234" }
```

**200:**

```json
{
  "success": true,
  "message": "Login realizado com sucesso.",
  "data": { "id": 1, "nome": "...", "login": "professor", "perfil": "professor" }
}
```

**422** campos vazios · **401** credenciais inválidas

#### `POST /auth/logout`

**200:** `{ "success": true, "message": "Logout realizado com sucesso." }`

#### `GET /auth/me`

**200:** `{ "success": true, "data": { ...user } }` · **401** sem sessão

#### `POST /auth/cadastro-aluno`

**Body:** `nome`, `celular`, `email`, `senha`, `confirmacao_senha` (mínimo 6 caracteres). Sem turma no pedido.

**201** solicitação criada · **409** e-mail já usado · **422** validação

### Painéis

#### `GET /professor/painel` — professor

Resumo para o dashboard docente.

#### `GET /aluno/painel` — aluno

Resumo para o dashboard do aluno aprovado.

### Turmas (somente professor)

#### `GET /turmas`

Lista turmas (`codigo`, `titulo`).

#### `POST /turmas`

**Body:** `{ "codigo": "TDS-2026", "titulo": "TDS 2026" }`

#### `POST /turmas/update`

**Body:** `id`, `codigo`, `titulo`.

### Disciplinas (somente professor)

#### `GET /disciplinas`

Catálogo do professor.

#### `POST /disciplinas`

**Body:** `{ "codigo": "BD", "titulo": "Banco de Dados" }`

### Aulas

#### `GET /aulas` — autenticado

Query opcional: `turma_id`, `disciplina_id`. Aluno vê só a própria turma.

#### `GET /aulas/detalhe?id=` — autenticado

Detalhe com vídeos e arquivos. Aluno só acessa aula da sua turma.

#### `POST /aulas` — professor (multipart)

Campos: `titulo`, `turma_id`, `disciplina_id`, `videos` (JSON de URLs YouTube), arquivos em `arquivos` (PDF/DOCX).

#### `POST /aulas/update` — professor (multipart)

Igual ao create, com `id` da aula.

### Alunos / solicitações (somente professor)

O aluno é um `usuarios`, não a tabela antiga `alunos`.

#### `GET /alunos`

Lista alunos **aprovados**.

#### `GET /alunos/pendentes`

Pedidos com `status=pendente`.

#### `POST /alunos/aprovar`

**Body:** `{ "id": 1, "turma_id": 1 }` — vincula a uma turma e aprova.

#### `POST /alunos/recusar`

**Body:** `{ "id": 1 }`

`MateriaController`, `RecadoController` e `DocumentoController` não estão ligados em `App.php`.

---

## Upload de aulas

`POST /aulas` e `POST /aulas/update` com `multipart/form-data`:

| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| `titulo` | string | sim |
| `turma_id` | int | sim |
| `disciplina_id` | int | sim |
| `videos` | JSON (URLs YouTube) | não |
| `arquivos` | file[] (PDF ou DOCX, até 10 MB) | não |
| `id` | int | só no update |

- Implementação: `LessonUpload`
- Arquivos em `storage/uploads/` com prefixo `aula_`
- 0..N vídeos e 0..N arquivos por aula

**201** na criação · **200** na atualização

---

## Códigos HTTP

| Código | Uso típico |
|--------|------------|
| 200 | Sucesso de leitura / login / logout |
| 201 | Criação |
| 401 | Não autenticado / credenciais inválidas |
| 403 | Autenticado, mas sem perfil professor |
| 404 | Rota ou recurso inexistente |
| 409 | Conflito (ex.: código de turma/disciplina ou e-mail duplicado) |
| 422 | Validação de entrada |
| 500 | Erro interno (`message` + `error` com detalhe da exceção) |

---

## Login padrão

| Campo | Valor |
|-------|-------|
| Login | `professor` |
| Senha | `1234` |
| Hash no banco | `sha1('1234')` = `7110eda4d09e062aa5e4a390b0a572ac0d2c0220` |

---

## Limitações conhecidas

1. **PHP 5.6** — manter sintaxe antiga.
2. **Senhas** — contas novas usam bcrypt quando `password_hash` existe; o seed do professor ainda é `sha1`.
3. **CORS `*`** — ok em same-origin; cookies de sessão não fluem bem em apps cross-origin.
4. **500** — o front controller devolve mensagem genérica; evite vazar detalhe em produção.
5. **Config SQLite** — driver env não chega automaticamente ao `Database` (ver README raiz).
6. **Pedido público** — `POST /auth/cadastro-aluno` cria aluno `pendente`; o professor aprova e escolhe a turma.
7. **Controllers legados** — `Materia`, `Recado` e `Documento` não têm rota registrada.

---

## Teste de impacto do Composer

Compara o autoload do Composer com o `Autoloader.php` interno (classes + respostas HTTP da API):

```bash
cd portal-aulas-ete/backend
composer install
php scripts/composer_impact_check.php
```

Saída esperada: `RESULTADO: nenhum impacto funcional detectado.`

---

## Como testar a API rapidamente

Com o servidor em `http://127.0.0.1:8000`:

```bash
curl "http://127.0.0.1:8000/backend/public/index.php?route=/health"
```

```bash
curl -X POST "http://127.0.0.1:8000/backend/public/index.php?route=/auth/login" ^
  -H "Content-Type: application/json" ^
  -d "{\"login\":\"professor\",\"senha\":\"1234\"}" ^
  -c cookies.txt
```

```bash
curl "http://127.0.0.1:8000/backend/public/index.php?route=/auth/me" -b cookies.txt
```
