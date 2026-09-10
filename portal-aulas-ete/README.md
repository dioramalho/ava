# Portal de Aulas — TDS

Front-end e ponto de entrada da aplicação AVA para as turmas **TDS**.

Documentação geral do repositório: [`../README.md`](../README.md).  
Documentação da API e do Composer: [`backend/README.md`](./backend/README.md).  
Casos de teste: [`casos-de-teste-jornada-professor.md`](./casos-de-teste-jornada-professor.md) e [`casos-de-teste-jornada-aluno.md`](./casos-de-teste-jornada-aluno.md).

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

- **Aulas** por turma e disciplina (YouTube + PDF/DOCX)
- Cadastros do professor (turmas, disciplinas, aulas)
- Pedido de acesso do aluno e **aprovação** pelo professor (uma turma)

Recados e matérias/documentos da versão anterior ficaram fora do escopo v1.

---

## Estrutura

```text
portal-aulas-ete/
├── index.html
├── cadastro-aluno.html
├── cadastro-enviado.html
├── dashboard.html
├── aluno-aulas.html
├── aluno-aula.html
├── aluno-aguardando.html
├── aluno-recusado.html
├── dashboard-professor.html
├── professor-turmas.html
├── professor-disciplinas.html
├── professor-aulas.html
├── professor-aula.html
├── professor-alunos.html
├── css/style.css
├── js/                    # sem bundler/npm
├── assets/
├── components/            # snippets ainda não acoplados
├── backend/               # API PHP + Composer (classmap)
├── casos-de-teste-jornada-aluno.md
├── casos-de-teste-jornada-professor.md
└── README.md
```

O front não usa npm. O **Composer** vale só para o backend (`backend/composer.json`). Sem `backend/vendor/`, a API usa `backend/app/core/Autoloader.php`.

---

## Como usar

1. (Opcional, PHP 7.2.5+ no CLI) instale o autoload do Composer:

   ```bash
   cd portal-aulas-ete/backend
   composer install
   ```

   Em XAMPP 5.6 o Composer 2 não roda; pule este passo.

2. Suba o servidor na raiz do portal (a partir da raiz do repo):

   ```bash
   php -S 127.0.0.1:8000 -t portal-aulas-ete
   ```

3. Abra `http://127.0.0.1:8000/index.html`.

4. Login padrão do professor:

   - **Login:** `professor`
   - **Senha:** `1234`

5. Fluxos:

   - Professor → `dashboard-professor.html` (turmas, disciplinas, aulas, alunos)
   - Aluno → pedido em `cadastro-aluno.html`; após aprovação, `dashboard.html`

6. Banco:

   - MySQL: importe `../portal_aulas_ete.sql`
   - SQLite: ver `backend/README.md` e o README raiz (há ressalvas de configuração)

---

## Páginas e fluxos

### Login (`index.html`)

1. Formulário envia `login` + `senha` via `POST /auth/login`.
2. Professor vai para `dashboard-professor.html`.
3. Aluno aprovado vai para `dashboard.html`; pendente → `aluno-aguardando.html`; recusado → `aluno-recusado.html`.
4. Link de cadastro aponta para `cadastro-aluno.html`.

### Pedido de aluno (`cadastro-aluno.html`)

- Nome, celular, e-mail, senha e confirmação. **Sem** escolha de turma.
- `POST /auth/cadastro-aluno` → `cadastro-enviado.html`.

### Dashboard aluno (`dashboard.html`)

- Valida sessão (`/auth/me` / `/aluno/painel`).
- Disciplinas com aula publicada na turma do aluno.
- Lista e detalhe: `aluno-aulas.html`, `aluno-aula.html`.

### Dashboard professor (`dashboard-professor.html`)

- Exige perfil `professor`.
- Atalhos para turmas, disciplinas, aulas e solicitações de alunos.

### Gestão do professor

- `professor-turmas.html` — CRUD de código/título
- `professor-disciplinas.html` — catálogo
- `professor-aulas.html` / `professor-aula.html` — publicar aula (YouTube + arquivos)
- `professor-alunos.html` — aprovar (escolhe a turma) ou recusar

---

## JavaScript

| Arquivo | Papel |
|---------|--------|
| `api.js` | `API_BASE_URL = 'backend/public/index.php?route='` e `apiRequest` |
| `session.js` | Login, logout, `validatePortalSession` |
| `cadastro-aluno.js` | Pedido público |
| `student.js` | Painel, lista e detalhe de aulas do aluno |
| `professor-dashboard.js` | Painel docente |
| `professor-turmas.js` | Turmas |
| `professor-disciplinas.js` | Disciplinas |
| `professor-aulas.js` | Lista de aulas |
| `professor-aula.js` | Criar/editar aula |
| `professor-alunos.js` | Aprovar / recusar |
| `professor.js` | Legado; as telas atuais usam os módulos `professor-*` |

### Contrato do `apiRequest`

- Sempre usa `credentials: 'same-origin'` (cookie de sessão).
- Espera JSON `{ success, message?, data? }`.
- Em HTTP não-OK, lança `Error` com `status` e `payload`.

### Guarda de sessão

```js
const user = await window.validatePortalSession({ requiredProfile: 'professor' });
```

- Sem sessão → `index.html`
- Perfil incorreto → redireciona conforme o perfil

---

## Estilos e assets

- `css/style.css` — identidade ETE (azul / amarelo / verde)
- Bootstrap 5.3 + Bootstrap Icons via CDN
- `assets/logo.svg` — marca

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

Publicação de aula: `FormData` com `titulo`, `turma_id`, `disciplina_id`, `videos` e `arquivos[]` → `POST /aulas`.

---

## Perfis e permissões na UI

| Perfil | Telas típicas | Observação |
|--------|---------------|------------|
| `professor` | dashboards e telas `professor-*` | Seed: `professor` / `1234` |
| `aluno` | dashboard e aulas | Conta em `usuarios`; `status` e `turma_id` |
| público | login, cadastro | Sem sessão |

A API reforça `requireProfessor()` / `requireAluno()` nas rotas correspondentes.

---

## Pontos de atenção

- Redirect e validação de sessão dependem de `js/session.js`.
- `js/professor.js` não é o script das páginas atuais.
- Composer no backend não altera o front; impacto do autoload: `backend/scripts/composer_impact_check.php`.
- Seed do professor ainda usa hash `sha1`; pedidos novos usam bcrypt quando o PHP oferece `password_hash`.

---

## Credenciais

| Login | Senha |
|-------|-------|
| `professor` | `1234` |
