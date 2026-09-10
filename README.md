# AVA — Ambiente Virtual de Aprendizado

Portal web para organização de aulas (YouTube + PDF/DOCX), turmas, disciplinas e alunos do curso **Técnico em Desenvolvimento de Sistemas (TDS)** da Escola Técnica Estadual (ETE).

O código da aplicação vive em [`portal-aulas-ete/`](./portal-aulas-ete/). Este README é o ponto central de documentação do repositório.

---

## Sumário

1. [Visão geral](#visão-geral)
2. [Stack tecnológica](#stack-tecnológica)
3. [Estrutura do repositório](#estrutura-do-repositório)
4. [Como executar localmente](#como-executar-localmente)
5. [Banco de dados](#banco-de-dados)
6. [Arquitetura](#arquitetura)
7. [Domínio e modelo de dados](#domínio-e-modelo-de-dados)
8. [Frontend](#frontend)
9. [API (backend)](#api-backend)
10. [Autenticação e sessão](#autenticação-e-sessão)
11. [Deploy](#deploy)
12. [Credenciais padrão](#credenciais-padrão)
13. [Convenções e restrições](#convenções-e-restrições)
14. [Estado atual e pontos de atenção](#estado-atual-e-pontos-de-atenção)
15. [Documentação relacionada](#documentação-relacionada)

---

## Visão geral

| Item | Descrição |
|------|-----------|
| **Nome** | AVA / Portal de Aulas — TDS |
| **Público** | Alunos e professores das turmas TDS |
| **Idioma** | Português (Brasil) |
| **Objetivo** | Publicar aulas por turma/disciplina e gerir pedidos de acesso de alunos |
| **Ambiente local** | XAMPP com PHP 5.6 (`C:\xampp56\...`) ou `php -S` |
| **Produção (develop)** | FTP em `ava.dev.diogoramalho.com.br` |

### Papéis

- **Professor** — autentica, cadastra turmas e disciplinas, publica aulas e aprova alunos (uma turma).
- **Aluno** — pede acesso, espera aprovação, autentica com o e-mail do cadastro e consulta aulas da turma.
- **Admin** — não há perfil `admin` no schema v1.

> **Importante:** o aluno é uma conta em `usuarios` (`perfil=aluno`, `status`, `turma_id`). A tabela `alunos` (matrícula antiga) não faz mais parte do schema vigente.

---

## Stack tecnológica

| Camada | Tecnologia |
|--------|------------|
| Frontend | HTML estático, CSS, Bootstrap 5.3, Bootstrap Icons (CDN), JavaScript vanilla |
| Backend | PHP 5.6, MVC próprio (sem framework), Composer (autoload classmap), PDO |
| API | JSON via `fetch`, sessão PHP (`$_SESSION`) |
| Banco | MySQL/MariaDB (`portal_aulas_ete.sql`) ou SQLite (schema em `backend/config/sqlite_schema.sql`) |
| Senhas | `PasswordHelper`: bcrypt (`password_hash`) nas contas novas; `sha1` ainda aceito no seed do professor |
| CI/CD | GitHub Actions: `composer install` + FTP (`develop`) |

**Compatibilidade PHP 5.6:** sem typed properties e sem nullsafe. Preferir `array()`, classes clássicas e sintaxe compatível. O Composer gerencia o autoload (`classmap`); em ambientes sem `vendor/` o `Autoloader.php` continua como fallback.

---

## Estrutura do repositório

```text
ava/
├── README.md                          # Documentação principal (este arquivo)
├── .gitignore                         # Ignora vendor/, composer.phar, logs
├── portal_aulas_ete.sql               # Schema + seed MySQL/MariaDB
├── .github/workflows/main.yml         # composer install + deploy FTP (develop)
└── portal-aulas-ete/                  # Aplicação
    ├── index.html                     # Login
    ├── cadastro-aluno.html            # Pedido público de acesso
    ├── dashboard.html                 # Painel do aluno
    ├── dashboard-professor.html       # Painel do professor
    ├── professor-*.html / aluno-*.html
    ├── css/style.css
    ├── js/                            # Scripts do front (sem bundler/npm)
    ├── assets/
    ├── casos-de-teste-jornada-*.md
    ├── README.md
    └── backend/
        ├── composer.json              # Dependências e autoload (classmap)
        ├── composer.lock
        ├── scripts/composer_impact_check.php
        ├── public/index.php           # Front controller da API
        ├── public/.htaccess
        ├── config/config.php
        ├── config/sqlite_schema.sql
        ├── app/core/                  # Inclui Autoloader.php (fallback)
        ├── app/controllers/
        ├── app/models/
        ├── storage/uploads/           # Uploads de aula (PDF/DOCX)
        └── README.md
```

`backend/vendor/` é gerado por `composer install` e **não** entra no Git nem no FTP. Sem `vendor/`, a API usa `app/core/Autoloader.php`.

---

## Como executar localmente

### Opção A — Servidor embutido do PHP

No backend (PHP 7.2.5+ no CLI do Composer 2). Em XAMPP 5.6 este passo pode ser pulado: a API cai no `Autoloader.php`.

```bash
cd portal-aulas-ete/backend
composer install
```

Na raiz do repositório:

```bash
php -S 127.0.0.1:8000 -t portal-aulas-ete
```

Abra: [http://127.0.0.1:8000/index.html](http://127.0.0.1:8000/index.html)

Com XAMPP 5.6 no Windows (sem Composer 2; usa o autoloader interno):

```bash
C:\xampp56\php\php.exe -S 127.0.0.1:8000 -t portal-aulas-ete
```

### Opção B — Apache (XAMPP)

1. Garanta que `htdocs/ava/portal-aulas-ete` esteja acessível pelo Apache.
2. Acesse algo como `http://localhost/ava/portal-aulas-ete/index.html`.
3. A API responde em `.../backend/public/index.php?route=...`.

O front usa URL relativa (`backend/public/index.php?route=`). As páginas HTML precisam ser servidas a partir da raiz de `portal-aulas-ete/`.

---

## Banco de dados

### MySQL / MariaDB (recomendado no XAMPP)

1. Importe [`portal_aulas_ete.sql`](./portal_aulas_ete.sql) no phpMyAdmin (ou CLI).
2. Isso cria o banco `portal_aulas_ete`, tabelas e dados iniciais.
3. Credenciais padrão do `config.php`: usuário `root`, senha vazia, host `localhost`, porta `3306`.

### SQLite (previsto no código)

- Schema e seed: `portal-aulas-ete/backend/config/sqlite_schema.sql`
- Arquivo esperado: `portal-aulas-ete/backend/storage/database.sqlite`
- Bootstrap automático ao criar o arquivo pela primeira vez (`Database::connectSqlite`)

### Variáveis de ambiente (documentadas)

| Variável | Descrição |
|----------|-----------|
| `PORTAL_DB_DRIVER` | `sqlite` ou `mysql` |
| `PORTAL_DB_HOST` | Host MySQL |
| `PORTAL_DB_PORT` | Porta MySQL |
| `PORTAL_DB_NAME` | Nome do banco |
| `PORTAL_DB_USER` | Usuário |
| `PORTAL_DB_PASSWORD` | Senha |

> **Atenção:** em `config.php`, o driver lido de `PORTAL_DB_DRIVER` ainda **não é repassado** no array retornado para `Database`. Sem ajuste, o runtime tende a cair no padrão MySQL. Detalhes em [Estado atual](#estado-atual-e-pontos-de-atenção).

---

## Arquitetura

```text
Browser (HTML/JS)
    │  fetch + credentials: 'same-origin'
    ▼
js/api.js  →  backend/public/index.php?route=/auth/login
    │
    ▼
session_start + CORS + Composer autoload (ou Autoloader)
    │
    ▼
App → Controller → Model (PDO) → MySQL/SQLite
    │
    ▼
Response JSON { success, message?, data?, id?, error? }
```

### Fluxo de uma requisição

1. Página HTML carrega `js/api.js` e o script da página.
2. `apiRequest('/rota', options)` chama `backend/public/index.php?route=/rota`.
3. `index.php` inicia sessão, trata `OPTIONS`, instancia `Request` e `App`.
4. `App` despacha para o controller/método registrados (apenas GET e POST).
5. Controllers usam `requireAuth()` / `requireProfessor()` / `requireAluno()` quando necessário.
6. Resposta JSON padronizada.

### Roteamento

- Principal: query string `?route=/caminho`
- Alternativo: path após `SCRIPT_NAME` (Apache + rewrite)
- Mutações usam **POST** (sem PUT/DELETE REST): `/turmas/update`, `/aulas/update`, `/alunos/aprovar`, `/alunos/recusar`

---

## Domínio e modelo de dados

```text
usuarios ── login, perfil, status, turma_id (aluno aprovado)
turmas
disciplinas ── catálogo do professor
aulas ── turma + disciplina
  ├── aula_videos     (YouTube)
  └── aula_arquivos   (PDF/DOCX)
```

| Tabela | Função |
|--------|--------|
| `usuarios` | Contas: `nome`, `login`, `senha`, `perfil`, `email`, `celular`, `status` (`pendente` \| `aprovado` \| `recusado`), `turma_id` |
| `turmas` | `codigo` + `titulo` |
| `disciplinas` | Catálogo: `codigo` + `titulo` |
| `aulas` | Aula publicada (turma + disciplina + título) |
| `aula_videos` | URLs YouTube da aula |
| `aula_arquivos` | Arquivos PDF/DOCX da aula |

Uploads ficam em `backend/storage/uploads/`. O seed SQL não depende mais de `materias` / `recados` / `documentos` / `alunos` (matrícula).

---

## Frontend

| Página | Scripts | Função |
|--------|---------|--------|
| `index.html` | `api.js`, `session.js` | Login |
| `cadastro-aluno.html` | `cadastro-aluno.js` | Pedido público de acesso |
| `cadastro-enviado.html` | — | Confirmação do pedido |
| `dashboard.html` | `student.js` | Painel do aluno |
| `aluno-aulas.html` / `aluno-aula.html` | `student.js` | Lista e detalhe de aulas |
| `aluno-aguardando.html` / `aluno-recusado.html` | — | Status do pedido |
| `dashboard-professor.html` | `professor-dashboard.js` | Painel do professor |
| `professor-turmas.html` | `professor-turmas.js` | Cadastro de turmas |
| `professor-disciplinas.html` | `professor-disciplinas.js` | Cadastro de disciplinas |
| `professor-aulas.html` / `professor-aula.html` | `professor-aulas.js` / `professor-aula.js` | Aulas (YouTube + PDF/DOCX) |
| `professor-alunos.html` | `professor-alunos.js` | Aprovação / recusa de alunos |

### Módulos JS

| Arquivo | Responsabilidade |
|---------|------------------|
| `js/api.js` | Base URL da API e helper `apiRequest` |
| `js/session.js` | Login, logout e `validatePortalSession` |
| `js/cadastro-aluno.js` | Pedido público (`POST /auth/cadastro-aluno`) |
| `js/student.js` | Dashboard do aluno |
| `js/professor-dashboard.js` | Painel do professor |
| `js/professor-turmas.js` | Turmas |
| `js/professor-disciplinas.js` | Disciplinas |
| `js/professor-aulas.js` / `professor-aula.js` | Listagem e edição de aulas |
| `js/professor-alunos.js` | Solicitações de alunos |

Identidade visual: tokens em `css/style.css` (cores ETE — azul/amarelo/verde).

Documentação de front mais detalhada: [`portal-aulas-ete/README.md`](./portal-aulas-ete/README.md).

---

## API (backend)

**Base:** `backend/public/index.php?route=`

**Formato de resposta (padrão):**

```json
{
  "success": true,
  "message": "opcional",
  "data": {}
}
```

Erros usam `success: false`, `message` e HTTP 4xx/5xx. Em 500, o campo `error` pode trazer a mensagem da exceção (útil em desenvolvimento; evitar expor em produção).

### Rotas

| Método | Rota | Auth | Descrição |
|--------|------|------|-----------|
| GET | `/` | — | Boas-vindas da API |
| GET | `/health` | — | Health check |
| POST | `/auth/login` | — | Login (`login`, `senha`) |
| POST | `/auth/logout` | sessão | Logout |
| GET | `/auth/me` | autenticado | Usuário da sessão |
| POST | `/auth/cadastro-aluno` | — | Pedido público (nome, celular, e-mail, senha) |
| GET | `/professor/painel` | professor | Resumo do painel docente |
| GET | `/aluno/painel` | aluno | Resumo do painel do aluno |
| GET | `/turmas` | professor | Lista turmas |
| POST | `/turmas` | professor | Cria turma (`codigo`, `titulo`) |
| POST | `/turmas/update` | professor | Atualiza turma |
| GET | `/disciplinas` | professor | Lista disciplinas |
| POST | `/disciplinas` | professor | Cria disciplina (`codigo`, `titulo`) |
| GET | `/aulas` | autenticado | Lista aulas (`?turma_id=` / `?disciplina_id=`) |
| GET | `/aulas/detalhe` | autenticado | Detalhe (`?id=`) |
| POST | `/aulas` | professor | Cria aula (multipart: vídeos YouTube + PDF/DOCX) |
| POST | `/aulas/update` | professor | Atualiza aula |
| GET | `/alunos` | professor | Alunos aprovados |
| GET | `/alunos/pendentes` | professor | Solicitações pendentes |
| POST | `/alunos/aprovar` | professor | Aprova e vincula à turma |
| POST | `/alunos/recusar` | professor | Recusa solicitação |

Classes `MateriaController`, `RecadoController` e `DocumentoController` ainda existem no código, mas **não** estão registradas em `App.php`. O upload vigente é o de aulas (`LessonUpload`, PDF/DOCX).

Referência completa de payloads: [`portal-aulas-ete/backend/README.md`](./portal-aulas-ete/backend/README.md).

---

## Autenticação e sessão

1. `POST /auth/login` com JSON `{ "login": "...", "senha": "..." }`.
2. Backend valida a senha com `PasswordHelper` (`password_verify` / bcrypt quando o hash for `$2y$`; `sha1` no seed antigo).
3. Em sucesso: `session_regenerate_id(true)` e `$_SESSION['user']` com `id`, `nome`, `login`, `perfil`.
4. Front usa `credentials: 'same-origin'` — HTML e API devem compartilhar origem (mesmo host/porta).
5. Páginas protegidas devem chamar `GET /auth/me` via `validatePortalSession` (quando habilitado em `session.js`).
6. Logout: `POST /auth/logout` + redirect para `index.html`.

---

## Deploy

Workflow: [`.github/workflows/main.yml`](./.github/workflows/main.yml)

- **Trigger:** push na branch `develop` ou disparo manual (`workflow_dispatch`)
- **Antes do FTP:** `composer install` em `portal-aulas-ete/backend` (valida `composer.lock`)
- **Ação:** FTPS explícito (porta 21, IPv4) de `./portal-aulas-ete/` para `/public_html/ava.dev.diogoramalho.com.br/`
- **Secrets:** `FTP_USERNAME`, `FTP_PASSWORD`
- **Espelho com `--delete`** — remove no remoto o que não existe no local
- **Exclusões:** `.git*`, `node_modules`, `.github`, `backend/vendor/**`, `backend/storage/uploads/**` (uploads preservados; produção usa o `Autoloader.php`)

---

## Credenciais padrão

| Campo | Valor |
|-------|-------|
| Login | `professor` |
| Senha | `1234` |
| Hash sha1 no seed | `7110eda4d09e062aa5e4a390b0a572ac0d2c0220` |

---

## Convenções e restrições

1. Manter sintaxe **PHP 5.6**.
2. Dependências PHP pelo Composer em `portal-aulas-ete/backend` (`composer.json` / `composer.lock`). Front sem npm/bundler.
3. Mensagens da API e UI em **PT-BR**.
4. Gestão de solicitações (`/alunos*`) exige perfil **professor**.
5. Material de aula: YouTube + arquivos **PDF** ou **DOCX** (até 10 MB).
6. Preferir caminhos relativos no front para a API continuar funcionando sob `php -S` e Apache.
7. `components/` ainda não está wired nas páginas (navbar/footer duplicados no HTML).

---

## Estado atual e pontos de atenção

Itens conhecidos no working tree / código (revisar antes de considerar o fluxo estável):

| Área | Situação |
|------|----------|
| Autenticação | `session.js` exporta `validatePortalSession`; login do professor no seed funciona com sha1 |
| Cadastro aluno | `POST /auth/cadastro-aluno` + aprovação em `/alunos/aprovar` |
| Composer | Autoload classmap; produção FTP sem `vendor/` usa `Autoloader.php` |
| `config.php` × SQLite | Driver `sqlite` lido via env pode não ser injetado no array `db` |
| Senhas | bcrypt nas contas novas; seed do professor ainda em `sha1('1234')` |
| Erros 500 | O front controller devolve mensagem genérica (não vaza a exceção no JSON) |

---

## Documentação relacionada

- [`portal-aulas-ete/README.md`](./portal-aulas-ete/README.md) — front, páginas e fluxos de uso
- [`portal-aulas-ete/backend/README.md`](./portal-aulas-ete/backend/README.md) — API, Composer e payloads
- [`portal-aulas-ete/backend/scripts/composer_impact_check.php`](./portal-aulas-ete/backend/scripts/composer_impact_check.php) — teste Composer vs Autoloader
- [`portal-aulas-ete/casos-de-teste-jornada-professor.md`](./portal-aulas-ete/casos-de-teste-jornada-professor.md)
- [`portal-aulas-ete/casos-de-teste-jornada-aluno.md`](./portal-aulas-ete/casos-de-teste-jornada-aluno.md)
- [`portal_aulas_ete.sql`](./portal_aulas_ete.sql) — schema MySQL + seed
- [`portal-aulas-ete/backend/config/sqlite_schema.sql`](./portal-aulas-ete/backend/config/sqlite_schema.sql) — schema SQLite + seed

---

## Licença / uso

Projeto educacional para o contexto ETE / TDS. Ajuste segurança (hash de senha, vazamento de erros, CORS) antes de qualquer uso em ambiente real com dados sensíveis.
