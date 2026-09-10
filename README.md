# AVA — Ambiente Virtual de Aprendizado

Portal web para organização de materiais em PDF, recados, turmas, matérias e alunos do curso **Técnico em Desenvolvimento de Sistemas (TDS)** da Escola Técnica Estadual (ETE).

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
| **Objetivo** | Publicar PDFs por matéria/turma, recados e gestão de alunos |
| **Ambiente local** | XAMPP com PHP 5.6 (`C:\xampp56\...`) ou `php -S` |
| **Produção (develop)** | FTP em `ava.dev.diogoramalho.com.br` |

### Papéis

- **Professor** — autentica, cria turmas/matérias, publica PDFs e recados, gerencia alunos (CRUD).
- **Aluno** — autentica (quando houver usuário com perfil `aluno`) e consulta recados e documentos.
- **Admin** — previsto no schema (`perfil`), ainda sem fluxo dedicado na UI.

> **Importante:** a tabela `alunos` representa matrícula/vínculo com turma. A tabela `usuarios` representa conta de login. São conceitos distintos.

---

## Stack tecnológica

| Camada | Tecnologia |
|--------|------------|
| Frontend | HTML estático, CSS, Bootstrap 5.3, Bootstrap Icons (CDN), JavaScript vanilla |
| Backend | PHP 5.6, MVC próprio (sem framework, sem Composer), PDO |
| API | JSON via `fetch`, sessão PHP (`$_SESSION`) |
| Banco | MySQL/MariaDB (`portal_aulas_ete.sql`) ou SQLite (schema em `backend/config/sqlite_schema.sql`) |
| Senhas | `sha1` (escolha pedagógica do projeto; não usar em produção real) |
| CI/CD | GitHub Actions → FTP (`develop`) |

**Compatibilidade PHP 5.6:** sem typed properties, sem nullsafe, sem Composer. Preferir `array()`, classes clássicas e sintaxe compatível.

---

## Estrutura do repositório

```text
ava/
├── README.md                          # Documentação principal (este arquivo)
├── portal_aulas_ete.sql               # Schema + seed MySQL/MariaDB
├── .github/workflows/main.yml         # Deploy FTP na branch develop
└── portal-aulas-ete/                  # Aplicação
    ├── index.html                     # Login
    ├── cadastro-aluno.html            # Auto-cadastro (em evolução)
    ├── dashboard.html                 # Painel do aluno
    ├── dashboard-professor.html       # Painel do professor
    ├── professor-alunos.html          # Gestão de alunos
    ├── css/style.css
    ├── js/                            # Scripts do front (sem bundler)
    ├── assets/logo.svg
    ├── assets/pdf/                    # PDFs de exemplo (seed)
    ├── components/                    # Snippets (ainda não integrados nas páginas)
    ├── README.md
    └── backend/
        ├── public/index.php           # Front controller da API
        ├── public/.htaccess
        ├── config/config.php
        ├── config/sqlite_schema.sql
        ├── app/core/                  # App, Request, Response, Database, Base*
        ├── app/controllers/
        ├── app/models/
        ├── storage/uploads/           # Uploads de PDF
        └── README.md
```

---

## Como executar localmente

### Opção A — Servidor embutido do PHP

Na raiz do repositório:

```bash
php -S 127.0.0.1:8000 -t portal-aulas-ete
```

Abra: [http://127.0.0.1:8000/index.html](http://127.0.0.1:8000/index.html)

Com XAMPP 5.6 no Windows:

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
session_start + CORS + Autoloader
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
5. Controllers usam `requireAuth()` / `requireProfessor()` quando necessário.
6. Resposta JSON padronizada.

### Roteamento

- Principal: query string `?route=/caminho`
- Alternativo: path após `SCRIPT_NAME` (Apache + rewrite)
- Atualizações/exclusões de alunos usam **POST** em `/alunos/update` e `/alunos/delete` (sem PUT/DELETE REST)

---

## Domínio e modelo de dados

```text
usuarios ── (login / perfil)
turmas
  ├── alunos          (matrícula na turma)
  ├── materias
  │     └── documentos (PDF)
  └── recados
```

| Tabela | Função |
|--------|--------|
| `usuarios` | Contas: `nome`, `login`, `senha` (sha1), `perfil` (`professor` \| `aluno` \| `admin`) |
| `turmas` | Cohortes (ex.: TDS 2024, TDS 2025) |
| `alunos` | Matrículas: `turma_id`, `nome`, `email`, `matricula` (única) |
| `materias` | Disciplinas por turma |
| `recados` | Avisos por turma |
| `documentos` | Metadados de PDF + `caminho_arquivo` |

PDFs seed ficam em `assets/pdf/...`. Uploads do professor ficam em `backend/storage/uploads/...`.

---

## Frontend

| Página | Scripts | Função |
|--------|---------|--------|
| `index.html` | `api.js`, `session.js` | Login |
| `dashboard.html` | + `student.js` | Recados e documentos (aluno) |
| `dashboard-professor.html` | + `professor.js` | Cadastros e publicações |
| `professor-alunos.html` | + `professor-alunos.js` | CRUD de alunos |
| `cadastro-aluno.html` | `api.js` (+ JS de cadastro em evolução) | Auto-cadastro público |

### Módulos JS

| Arquivo | Responsabilidade |
|---------|------------------|
| `js/api.js` | Base URL da API e helper `apiRequest` |
| `js/session.js` | Login, logout e (quando ativo) `validatePortalSession` |
| `js/student.js` | Dashboard do aluno |
| `js/professor.js` | Dashboard do professor |
| `js/professor-alunos.js` | Tela de gestão de alunos |

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
| GET | `/turmas` | autenticado | Lista turmas |
| POST | `/turmas` | autenticado | Cria turma |
| GET | `/alunos` | professor | Lista alunos |
| POST | `/alunos` | professor | Cria aluno |
| POST | `/alunos/update` | professor | Atualiza aluno |
| POST | `/alunos/delete` | professor | Remove aluno |
| GET | `/materias` | autenticado | Lista (`?turma_id=`) |
| POST | `/materias` | autenticado | Cria matéria |
| GET | `/recados` | autenticado | Lista (`?turma_id=`) |
| POST | `/recados` | autenticado | Cria recado |
| GET | `/documentos` | autenticado | Lista (`?turma_id=`) |
| POST | `/documentos` | autenticado | Upload PDF (`multipart`, campo `arquivo`) |

Referência completa de payloads: [`portal-aulas-ete/backend/README.md`](./portal-aulas-ete/backend/README.md).

---

## Autenticação e sessão

1. `POST /auth/login` com JSON `{ "login": "...", "senha": "..." }`.
2. Backend compara `sha1($senha)` com `usuarios.senha`.
3. Em sucesso: `session_regenerate_id(true)` e `$_SESSION['user']` com `id`, `nome`, `login`, `perfil`.
4. Front usa `credentials: 'same-origin'` — HTML e API devem compartilhar origem (mesmo host/porta).
5. Páginas protegidas devem chamar `GET /auth/me` via `validatePortalSession` (quando habilitado em `session.js`).
6. Logout: `POST /auth/logout` + redirect para `index.html`.

---

## Deploy

Workflow: [`.github/workflows/main.yml`](./.github/workflows/main.yml)

- **Trigger:** push na branch `develop` ou disparo manual (`workflow_dispatch`)
- **Ação:** FTPS explícito (porta 21, IPv4) de `./portal-aulas-ete/` para `/public_html/ava.dev.diogoramalho.com.br/`
- **Secrets:** `FTP_USERNAME`, `FTP_PASSWORD`
- **Espelho com `--delete`** — remove no remoto o que não existe no local
- **Exclusões:** `.git*`, `node_modules`, `.github`, `backend/storage/uploads/**` (uploads preservados)

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
2. Não introduzir Composer/npm/build step sem decisão explícita.
3. Mensagens da API e UI em **PT-BR**.
4. Endpoints de `/alunos*` exigem perfil **professor**.
5. Upload de documentos: apenas **PDF**.
6. Preferir caminhos relativos no front para a API continuar funcionando sob `php -S` e Apache.
7. `components/` ainda não está wired nas páginas (navbar/footer duplicados no HTML).

---

## Estado atual e pontos de atenção

Itens conhecidos no working tree / código (revisar antes de considerar o fluxo estável):

| Área | Situação |
|------|----------|
| Login (`User.php`) | Pode haver `die()`/debug bloqueando `findByLogin` |
| `session.js` | Redirect pós-login e `validatePortalSession` podem estar comentados |
| Dashboards | Dependem de `window.validatePortalSession` — se ausente, falham no init |
| Cadastro aluno | HTML existe; JS/endpoint/schema de auto-cadastro ainda em evolução |
| `config.php` × SQLite | Driver `sqlite` lido via env pode não ser injetado no array `db` |
| Senhas | `sha1` inadequado para produção |
| Erros 500 | Mensagem interna da exceção pode vazar no JSON |

Ordem sugerida para desbloquear autenticação:

1. Remover debug em `User.php`
2. Restaurar redirect e `validatePortalSession` em `session.js`
3. Confirmar driver/banco em `config.php` + `Database.php`
4. Implementar cadastro (JS + rota + modelo de dados)

---

## Documentação relacionada

- [`portal-aulas-ete/README.md`](./portal-aulas-ete/README.md) — front, páginas e fluxos de uso
- [`portal-aulas-ete/backend/README.md`](./portal-aulas-ete/backend/README.md) — API, configuração e payloads
- [`portal_aulas_ete.sql`](./portal_aulas_ete.sql) — schema MySQL + seed
- [`portal-aulas-ete/backend/config/sqlite_schema.sql`](./portal-aulas-ete/backend/config/sqlite_schema.sql) — schema SQLite + seed

---

## Licença / uso

Projeto educacional para o contexto ETE / TDS. Ajuste segurança (hash de senha, vazamento de erros, CORS) antes de qualquer uso em ambiente real com dados sensíveis.
