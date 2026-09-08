# Backend PHP — Portal de Aulas TDS

API JSON em **PHP 5.6**, **PDO**, **POO** e **MVC enxuto**, sem Composer e sem framework.

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
8. [Upload de documentos](#upload-de-documentos)
9. [Códigos HTTP](#códigos-http)
10. [Login padrão](#login-padrão)
11. [Limitações conhecidas](#limitações-conhecidas)

---

## Estrutura

```text
backend/
├── app/
│   ├── controllers/     # Auth, Turma, Aluno, Materia, Recado, Documento
│   ├── core/            # App, Request, Response, Database, Controller, BaseModel, Autoloader
│   └── models/
├── config/
│   ├── config.php
│   └── sqlite_schema.sql
├── public/
│   ├── index.php        # Front controller
│   └── .htaccess
├── storage/
│   └── uploads/         # PDFs enviados
└── README.md
```

---

## Requisitos

- PHP **5.6+** (ambiente de referência: XAMPP 5.6.40)
- Extensão PDO + driver MySQL e/ou SQLite
- Apache com `mod_rewrite` (opcional) **ou** `php -S`
- Sessões PHP habilitadas

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
3. Autoload das classes
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
| `Controller` | `json()`, `requireAuth()`, `requireProfessor()` |
| `*Controller` | Regras de cada recurso |
| `BaseModel` / models | Acesso PDO às tabelas |
| `Database` | Singleton PDO (MySQL ou SQLite) |

---

## Autenticação

| Rota | Comportamento |
|------|----------------|
| `POST /auth/login` | Valida `login`/`senha`, compara `sha1($senha)`, grava `$_SESSION['user']` |
| `GET /auth/me` | Exige sessão; devolve usuário |
| `POST /auth/logout` | Limpa e destrói sessão |

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

### Turmas

#### `GET /turmas` — autenticado

Retorna `data` com lista de turmas.

#### `POST /turmas` — autenticado

**Body:**

```json
{
  "nome": "TDS 2026",
  "ano_letivo": "2026",
  "turno": "Noite",
  "descricao": "opcional"
}
```

Campos obrigatórios: `nome`, `ano_letivo`, `turno`.

### Alunos (somente professor)

Campos de domínio: `turma_id`, `nome`, `email`, `matricula`.

#### `GET /alunos`

Lista matrículas.

#### `POST /alunos`

Cria. **201** com `id`. **409** se matrícula duplicada.

#### `POST /alunos/update`

**Body** inclui `id` + demais campos obrigatórios.

#### `POST /alunos/delete`

**Body:** `{ "id": 1 }`

### Matérias

#### `GET /materias?turma_id=` — autenticado

Filtro opcional por turma.

#### `POST /materias` — autenticado

**Body típico:** `turma_id`, `nome`, `descricao` (opcional).

### Recados

#### `GET /recados?turma_id=` — autenticado

#### `POST /recados` — autenticado

**Body típico:** `turma_id`, `titulo`, `mensagem`.

### Documentos

#### `GET /documentos?turma_id=` — autenticado

#### `POST /documentos` — autenticado (multipart)

Ver seção [Upload](#upload-de-documentos).

---

## Upload de documentos

`POST /documentos` com `multipart/form-data`:

| Campo | Tipo | Obrigatório |
|-------|------|-------------|
| `turma_id` | int | sim |
| `materia_id` | int | sim |
| `titulo` | string | sim |
| `descricao` | string | não |
| `arquivo` | file (PDF) | sim |

- Apenas extensão `.pdf`
- Arquivo salvo em `storage/uploads/` com nome `uniqid('pdf_', true) . '.pdf'`
- `caminho_arquivo` persistido como `backend/storage/uploads/<arquivo>`

**201** com `id` em sucesso.

---

## Códigos HTTP

| Código | Uso típico |
|--------|------------|
| 200 | Sucesso de leitura / login / logout |
| 201 | Criação |
| 401 | Não autenticado / credenciais inválidas |
| 403 | Autenticado, mas sem perfil professor |
| 404 | Rota ou recurso inexistente |
| 409 | Conflito (ex.: matrícula duplicada) |
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
2. **sha1** — inadequado para produção; preferir `password_hash` / `password_verify` em evolução futura.
3. **CORS `*`** — ok em same-origin; cookies de sessão não fluem bem em apps cross-origin.
4. **500 vazam `error`** — mensagem de exceção no JSON.
5. **Config SQLite** — driver env não chega automaticamente ao `Database` (ver README raiz).
6. **Sem registro público** — não há `POST /auth/register` ainda (cadastro-aluno no front está em evolução).
7. **`usuarios` ≠ `alunos`** — login e matrícula são entidades separadas.

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
