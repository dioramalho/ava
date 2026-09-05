# Portal de Aulas — TDS

Front-end e ponto de entrada da aplicação AVA para as turmas **TDS 2024** e **TDS 2025**.

Documentação geral do repositório: [`../README.md`](../README.md).  
Documentação da API: [`backend/README.md`](./backend/README.md).

---

## Sumário

1. [Objetivo](#objetivo)
2. [Estrutura](#estrutura)
3. [Como usar](#como-usar)
4. [Páginas e fluxos](#páginas-e-fluxos)
5. [JavaScript](#javascript)
6. [Estilos e assets](#estilos-e-assets)
7. [Integração com a API](#integração-com-a-api)
8. [Perfis e permissões na UI](#perfis-e-permissões-na-ui)
9. [Pontos de atenção](#pontos-de-atenção)

---

## Objetivo

Organizar e exibir:

- Materiais em **PDF** por turma e matéria
- **Recados** por turma
- Cadastros do professor (turmas, matérias, documentos, recados)
- Gestão de **alunos** (matrículas) pelo professor

---

## Estrutura

```text
portal-aulas-ete/
├── index.html                  # Login
├── cadastro-aluno.html         # Auto-cadastro (em evolução)
├── dashboard.html              # Área do aluno
├── dashboard-professor.html    # Área do professor
├── professor-alunos.html       # CRUD de alunos
├── css/style.css
├── js/
│   ├── api.js                  # Cliente HTTP da API
│   ├── session.js              # Login / logout / guarda de sessão
│   ├── student.js
│   ├── professor.js
│   └── professor-alunos.js
├── assets/
│   ├── logo.svg
│   └── pdf/                    # PDFs do seed
├── components/                 # navbar/footer (ainda não acoplados)
├── backend/                    # API PHP MVC
└── README.md
```

Não há bundler, npm nem build step: abra os HTML servidos por PHP/Apache.

---

## Como usar

1. Suba o servidor na raiz do portal (a partir da raiz do repo):

   ```bash
   php -S 127.0.0.1:8000 -t portal-aulas-ete
   ```

2. Abra `http://127.0.0.1:8000/index.html`.

3. Login padrão:

   - **Login:** `professor`
   - **Senha:** `1234`

4. Com sessão válida:

   - Aluno → `dashboard.html`
   - Professor → `dashboard-professor.html` e `professor-alunos.html`

5. Banco:

   - MySQL: importe `../portal_aulas_ete.sql`
   - SQLite: ver `backend/README.md` e o README raiz (há ressalvas de configuração)

---

## Páginas e fluxos

### Login (`index.html`)

1. Formulário envia `login` + `senha` via `POST /auth/login`.
2. Em sucesso, o fluxo previsto redireciona para `dashboard.html`.
3. Link “Cadastre-se” aponta para `cadastro-aluno.html`.

### Dashboard aluno (`dashboard.html`)

- Valida sessão (`/auth/me`).
- Lista recados e documentos (agrupados por matéria/turma).
- Logout via botão → `POST /auth/logout` → `index.html`.

### Dashboard professor (`dashboard-professor.html`)

- Exige perfil `professor` (quando a guarda de sessão estiver ativa).
- Permite cadastrar turma, matéria, publicar recado e enviar PDF.
- Navegação para gestão de alunos.

### Gestão de alunos (`professor-alunos.html`)

- CRUD completo contra `/alunos`, `/alunos/update`, `/alunos/delete`.
- Campos: turma, nome, e-mail, matrícula.

### Cadastro aluno (`cadastro-aluno.html`)

- Formulário público (nome, celular, e-mail, senha).
- Em evolução: falta consolidar JS, endpoint e mapeamento no schema (`usuarios` vs `alunos`).

---

## JavaScript

| Arquivo | Papel |
|---------|--------|
| `api.js` | `API_BASE_URL = 'backend/public/index.php?route='` e `apiRequest(route, options)` |
| `session.js` | Submit do login, logout, `validatePortalSession` (quando exportado) |
| `student.js` | Carrega dados do aluno após validar sessão |
| `professor.js` | Formulários e listagens do painel docente |
| `professor-alunos.js` | CRUD de matrículas |

### Contrato do `apiRequest`

- Sempre usa `credentials: 'same-origin'` (cookie de sessão).
- Espera JSON `{ success, message?, data? }`.
- Em HTTP não-OK, lança `Error` com `status` e `payload`.

### Guarda de sessão

Padrão previsto:

```js
const user = await window.validatePortalSession({ requiredProfile: 'professor' });
```

- Sem sessão → redirect para `index.html`
- Perfil incorreto → redirect para `dashboard.html`

Se `validatePortalSession` estiver comentado/ausente, os dashboards quebram no `await`.

---

## Estilos e assets

- `css/style.css` — identidade ETE (azul / amarelo / verde)
- Bootstrap 5.3 + Bootstrap Icons via CDN
- `assets/logo.svg` — marca
- `assets/pdf/*.pdf` — materiais iniciais referenciados no seed

`components/navbar.html` e `footer.html` existem como snippets; as páginas atuais embutem header/footer no próprio HTML.

---

## Integração com a API

Todas as páginas autenticadas dependem do mesmo origin da API.

Exemplo de login:

```js
await apiRequest('/auth/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ login: username, senha: password })
});
```

Upload de PDF (professor): `FormData` com campos `turma_id`, `materia_id`, `titulo`, `descricao` e arquivo `arquivo` → `POST /documentos`.

---

## Perfis e permissões na UI

| Perfil | Telas típicas | Observação |
|--------|---------------|------------|
| `professor` | dashboards + alunos | Único perfil seedado |
| `aluno` | `dashboard.html` | Conta em `usuarios`; distinto da tabela `alunos` |
| público | login, cadastro | Sem sessão |

A API reforça `requireProfessor()` nas rotas de alunos; a UI não deve chamar `/alunos*` sem perfil professor.

---

## Pontos de atenção

- Redirect pós-login e validação de sessão em `js/session.js` podem estar desabilitados temporariamente (debug).
- README antigo citava `js/auth.js` — o arquivo real é `js/session.js`.
- Auto-cadastro ainda não fecha o ciclo front ↔ API ↔ banco.
- Senhas em `sha1` — adequado só ao escopo pedagógico atual.
- Backend PHP 5.6: detalhes em [`backend/README.md`](./backend/README.md).

---

## Credenciais

| Login | Senha |
|-------|-------|
| `professor` | `1234` |
