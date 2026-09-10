# Casos de teste — Jornada do aluno (v1)

Plano para validar o fluxo do aluno definido com o Product Owner: o aluno **pede acesso** (sem escolher turma), espera o professor **aprovar e vincular a uma única turma**, entra com o **e-mail do cadastro** e consome **aulas publicadas** daquela turma (YouTube + PDF/DOCX).

Complementa [`casos-de-teste-jornada-professor.md`](./casos-de-teste-jornada-professor.md). A aprovação em si é P0 do professor (JP-07); aqui o oráculo é o que o **aluno** vê e o que **não** pode fazer.

**Fora do escopo v1:** aluno escolher turma no cadastro, várias turmas, recados, cadastro pelo professor, rascunho de aula.

**Login do aluno:** e-mail usado como `login` + senha criada no pedido (mínimo 6 caracteres).

**Ambiente:** `http://localhost/ava/portal-aulas-ete/`

O Composer no backend não altera este plano: o aluno continua falando só com as rotas de cadastro, sessão e aulas. Sem `vendor/`, vale o `Autoloader.php`.

**Prioridade:** P0 bloqueia release · P1 fluxo principal · P2 qualidade / borda.

**Telas da jornada:**

```text
index.html (Entrar / Solicitar cadastro)
  → cadastro-aluno.html
    → cadastro-enviado.html
  → (login pendente) aluno-aguardando.html
  → (login recusado) aluno-recusado.html
  → (aprovado) dashboard.html
       → aluno-aulas.html
            → aluno-aula.html
```

---

## Premissas de produto (oráculo)

| Decisão | Comportamento esperado |
|---|---|
| Pedido público | Nome, celular, e-mail, senha e confirmação. **Sem** select de turma |
| Status inicial | `perfil=aluno`, `status=pendente`, `turma_id` nulo. **Não** abre sessão de portal |
| Aprovação | Só o professor escolhe a turma; v1 = **uma** turma |
| Recusa | Login não entra no conteúdo; mensagem clara de pedido não aprovado |
| Identidade | Aluno entra com o **mesmo e-mail** do cadastro |
| Conteúdo | Só aulas da própria turma; salvar no painel docente = já visível |
| Disciplinas na home | Só disciplinas que **já têm aula** na turma do aluno (catálogo filtrado pelo conteúdo publicado) |
| Recados | Não aparecem no menu nem no início do aluno |
| Papel | Sem cadastro de turma, disciplina, aula ou aprovação de colegas |

---

## JA-01 — Pedido de acesso (público)

### JA-01.01 — Enviar solicitação válida (P0)

**Objetivo:** o aluno cria um pedido pendente sem ganhar acesso.

**Pré-condição:** e-mail ainda não cadastrado.

**Passos:**

1. Em `index.html`, clicar **Solicitar cadastro**.
2. Conferir aviso: cadastro **não** libera o portal na hora.
3. Preencher nome, celular, e-mail válido, senha (≥6) e confirmação iguais.
4. **Enviar solicitação**.

**Esperado:**

- `POST /auth/cadastro-aluno` **201**.
- Registro `pendente`, login = e-mail (normalizado em minúsculas), senha com hash, sem `turma_id`.
- Redirect para `cadastro-enviado.html`.
- Texto: não é possível entrar agora; usar o mesmo e-mail quando o professor aprovar.
- Pedido aparece na fila do professor (JP-07.01).
- **Não** cria cookie de sessão de aluno autenticado no portal de aulas.

---

### JA-01.02 — Campos obrigatórios (P1)

**Passos:** enviar sem nome, sem celular, sem e-mail ou sem senha (um por vez).

**Esperado:** 422 “Nome, celular, e-mail e senha são obrigatórios.” Nada gravado.

---

### JA-01.03 — E-mail inválido (P1)

**Passos:** `aluno@`, `sem-arroba`, espaços.

**Esperado:** 422 “Informe um e-mail válido.”

---

### JA-01.04 — Senhas diferentes (P0)

**Passos:** senha `abcdef` e confirmação `abcdeg`.

**Esperado:** mensagem na tela e/ou API 422 “A confirmação de senha não confere.” Sem registro.

---

### JA-01.05 — Senha curta (P1)

**Passos:** senha com 5 caracteres.

**Esperado:** 422 “A senha deve ter pelo menos 6 caracteres.”

---

### JA-01.06 — E-mail duplicado (P0)

**Passos:** repetir o cadastro com o mesmo e-mail (pendente, aprovado ou recusado).

**Esperado:** **409** “Já existe uma solicitação ou conta com este e-mail.” Não cria segundo usuário.

---

### JA-01.07 — Aluno não escolhe turma no pedido (P0)

**Passos:** inspecionar `cadastro-aluno.html` e o body da API.

**Esperado:** nenhum campo `turma_id`. Payload só dados pessoais + senha.

---

### JA-01.08 — Voltar ao login a partir do cadastro e do “enviado” (P2)

**Esperado:** links **Entrar** / **Voltar para o login** abrem `index.html`.

---

## JA-02 — Login e estados (pendente, recusado, aprovado)

### JA-02.01 — Login do aluno aprovado (P0)

**Pré-condição:** aluno aprovado em TDS-2025; e-mail `ana@email.com`; senha conhecida.

**Passos:** em `index.html`, login = e-mail, senha correta, **Entrar**.

**Esperado:**

- 200; `perfil=aluno`; `turma_id` preenchido.
- Redirect para `dashboard.html` (não para o painel do professor).
- Menu: **Início**, **Minhas aulas**, **Sair**.

---

### JA-02.02 — Login com senha errada (P0)

**Esperado:** 401 “Credenciais inválidas.” (ou equivalente). Permanece no login. Sem sessão.

---

### JA-02.03 — Tentativa de login ainda pendente (P0)

**Pré-condição:** pedido enviado, professor ainda não analisou.

**Passos:** e-mail + senha corretos do pedido.

**Esperado:**

- **403** “Seu acesso ainda não foi aprovado pelo professor.”
- Sem `$_SESSION['user']`.
- UI: `aluno-aguardando.html` (ou alerta equivalente + destino dessa tela).
- URLs `dashboard.html` / `aluno-aulas.html` / `aluno-aula.html` não abrem conteúdo.

---

### JA-02.04 — Tentativa de login recusado (P0)

**Esperado:** 403 “Sua solicitação de acesso foi recusada.” Tela `aluno-recusado.html`. Sem sessão. Sem aulas.

---

### JA-02.05 — Login do professor não cai na área do aluno (P1)

**Passos:** autenticar `professor` / `1234`.

**Esperado:** `dashboard-professor.html`. Abrir `dashboard.html` redireciona de volta ao painel docente.

---

### JA-02.06 — Telas do aluno sem sessão (P0)

**Passos:** anônimo em `dashboard.html`, `aluno-aulas.html`, `aluno-aula.html` (com e sem `?id=`).

**Esperado:** redirect `index.html`. `GET /auth/me` 401. `GET /aulas` 401.

---

### JA-02.07 — Logout do aluno (P1)

**Esperado:** `POST /auth/logout` 200; `index.html`; voltar no browser não reabre Início autenticado.

---

### JA-02.08 — Após aprovação, o mesmo e-mail passa a funcionar (P0)

**Passos:** login pendente (403) → professor aprova na TDS-2026 → mesmo e-mail/senha.

**Esperado:** 200 e Início mostrando **TDS-2026**. Sem novo cadastro.

---

## JA-03 — Início (dashboard do aluno)

### JA-03.01 — Saudação e turma (P0)

**Pré-condição:** aluno aprovado; dados reais na API.

**Esperado:**

- Nome do aluno (não mock estático na versão ligada).
- Código e título da **única** turma.
- Sem seletor para trocar de turma.

---

### JA-03.02 — Disciplinas da turma (P0)

**Esperado:** cards só de disciplinas que têm **pelo menos uma aula** na turma do aluno. Contagem de aulas por disciplina coerente com o banco. Clique leva a **Minhas aulas** (filtradas ou com a disciplina em contexto).

---

### JA-03.03 — Aulas recentes (P1)

**Esperado:** últimas aulas **da própria turma**, com disciplina e resumo de material (N vídeos · N arquivos). **Abrir** vai para o detalhe correto (`aluno-aula.html?id=`).

---

### JA-03.04 — Turma sem aulas ainda (P1)

**Pré-condição:** aluno aprovado; zero aulas na turma.

**Esperado:** mensagem vazia clara (disciplinas vazias e/ou “nenhuma aula publicada”). Sem erro técnico. Sem aulas de outras turmas para “preencher”.

---

### JA-03.05 — Recados ausentes (P1)

**Esperado:** nenhum bloco de recado no Início nem no menu.

---

## JA-04 — Minhas aulas

### JA-04.01 — Lista só da turma do aluno (P0)

**Pré-condição:** aula A em TDS-2025, aula B em TDS-2024; aluno só em TDS-2025.

**Passos:** abrir `aluno-aulas.html`. Chamar `GET /aulas` autenticado como aluno.

**Esperado:** só A. Query `turma_id` do aluno **não** pode ser sobrescrita para ver B (API força `turma_id` da sessão).

---

### JA-04.02 — Filtrar por disciplina (P1)

**Passos:** select **Todas**; depois uma disciplina.

**Esperado:** `disciplina_id` reduz a lista. Disciplinas no filtro = as da turma com conteúdo (ou catálogo usado nas aulas da turma). Não listar disciplinas só de outra turma.

---

### JA-04.03 — Colunas e ação Abrir (P1)

**Esperado:** título, disciplina, resumo de material, botão **Abrir** para o detalhe daquela aula.

---

## JA-05 — Detalhe da aula (consumo)

### JA-05.01 — Abrir aula da própria turma (P0)

**Passos:** `GET /aulas/detalhe?id=` da aula da turma; UI `aluno-aula.html`.

**Esperado:** título, turma, disciplina; todos os vídeos YouTube (embed `youtube.com/embed/...` ou player equivalente); todos os arquivos com nome original; PDF e DOCX baixáveis/abrem em nova aba.

---

### JA-05.02 — Vários vídeos e vários arquivos (P0)

**Pré-condição:** aula com 2 YouTube + PDF + DOCX (JP-05.05).

**Esperado:** os quatro itens visíveis na ordem gravada. Embeds distintos (não o mesmo vídeo duas vezes por engano).

---

### JA-05.03 — Aula sem material (P1)

**Esperado:** página abre; área de vídeos/arquivos vazia com texto amigável, sem quebrar layout.

---

### JA-05.04 — Aula de outra turma (P0)

**Passos:** aluno TDS-2025 abre `aluno-aula.html?id=` de aula TDS-2024 (URL adivinhada). `GET /aulas/detalhe`.

**Esperado:** **403** “Acesso não autorizado.” UI não mostra título nem material. Não vazar JSON da aula.

---

### JA-05.05 — ID inexistente (P1)

**Esperado:** 404 “Aula não encontrada.” Mensagem na UI, não página em branco de mock.

---

### JA-05.06 — Aula publicada depois que o aluno já estava logado (P0)

**Passos:** aluno na lista → professor publica nova aula na mesma turma → aluno atualiza **Minhas aulas**.

**Esperado:** a aula nova aparece **sem** novo login (publicação imediata).

---

### JA-05.07 — Voltar para a lista (P2)

**Esperado:** link **Voltar para minhas aulas** funciona.

---

### JA-05.08 — URL YouTube maliciosa não vira script (P1)

**Pré-condição:** se o professor tiver gravado URL estranha (caso de borda); aluno abre a aula.

**Esperado:** só embed/link YouTube sanitizado; sem `javascript:` nem iframe de domínio arbitrário.

---

## JA-06 — Isolamento e autorização

### JA-06.01 — Aluno não acessa painel do professor (P0)

**Passos:** sessão aluno; URLs `dashboard-professor.html`, `professor-turmas.html`, `professor-aula.html`, `professor-alunos.html`.

**Esperado:** redirect para `dashboard.html`. APIs `POST /turmas`, `POST /aulas`, `GET /alunos/pendentes`, `POST /alunos/aprovar` → **403**.

---

### JA-06.02 — Aluno não lista pendentes nem colegas além do necessário (P1)

**Esperado:** `GET /alunos` e `GET /alunos/pendentes` 403. Início não mostra fila de solicitações.

---

### JA-06.03 — Aluno não força outra turma na API de aulas (P0)

**Passos:** `GET /aulas?turma_id=` de outra turma, autenticado como aluno.

**Esperado:** resposta equivalente à turma da sessão (ignora o query). Nunca lista a outra turma.

---

### JA-06.04 — Arquivo da aula de outra turma (P0)

**Passos:** URL direta do arquivo em `storage/uploads` de aula de outra turma (se o servidor servir o arquivo).

**Esperado (v1 desejável):** download autenticado ou 403. Se o Apache servir arquivo estático sem checagem, registrar como **risco conhecido** e não como aceite silencioso.

---

## JA-07 — Cadastro e dados pessoais (qualidade)

### JA-07.01 — Celular chega na fila do professor (P1)

**Esperado:** valor informado no pedido aparece para o professor (nome, e-mail, celular).

---

### JA-07.02 — XSS no nome do aluno (P1)

**Passos:** nome `<script>alert(1)</script>`.

**Esperado:** texto escapado no Início, na fila do professor e nas listas. Sem script executado.

---

### JA-07.03 — Máscara/tamanho de celular (P2)

**Esperado:** campo usável (placeholder/máscara); valor persistido de forma consistente. Recusa vazia já coberta em JA-01.02.

---

## JA-08 — UX e identidade

### JA-08.01 — Marca ETE nas telas públicas e internas (P1)

**Esperado:** logo em destaque no pedido, enviado, aguardando, recusado, login e navbar do aluno. Faixa amarelo/verde/vermelho.

---

### JA-08.02 — Menu do aluno (P1)

**Esperado:** só Início · Minhas aulas · Sair. Sem Turmas/Disciplinas/Alunos de gestão.

---

### JA-08.03 — Responsivo (P2)

**Passos:** 375px e 1440px em cadastro, Início, lista e detalhe com vídeo.

**Esperado:** formulário e player usáveis; botões tocáveis; menu colapsa.

---

## JA-09 — Jornada ponta a ponta (P0)

Simula o aluno do zero até consumir a aula, cruzando com o professor.

| Passo | Ação | Critério de aceite |
|---|---|---|
| 1 | Solicitar cadastro válido | 201; tela “pedido enviado”; status pendente |
| 2 | Tentar Entrar | 403; aguardando; sem aulas |
| 3 | Professor aprova na TDS-2025 | JP-07.02 |
| 4 | Entrar com o mesmo e-mail | Início com TDS-2025 |
| 5 | Abrir Minhas aulas | Só aulas dessa turma |
| 6 | Abrir uma aula com vídeo + PDF | Embed e download |
| 7 | Colega em TDS-2024 | Não vê essa aula |
| 8 | Login recusado (outro aluno) | Tela recusado |
| 9 | Sair | Login público |

**Falha em qualquer passo P0 desta tabela = jornada do aluno v1 não está pronta.**

---

## Matriz rápida

| ID | Módulo | Tipo | P |
|---|---|---|---|
| JA-01.01 | Pedido | Feliz | P0 |
| JA-01.02 | Pedido | Validação | P1 |
| JA-01.03 | Pedido | E-mail | P1 |
| JA-01.04 | Pedido | Senha | P0 |
| JA-01.05 | Pedido | Senha | P1 |
| JA-01.06 | Pedido | Unicidade | P0 |
| JA-01.07 | Pedido | Sem turma | P0 |
| JA-01.08 | Pedido | Navegação | P2 |
| JA-02.01 | Login | Feliz | P0 |
| JA-02.02 | Login | Negativo | P0 |
| JA-02.03 | Login | Pendente | P0 |
| JA-02.04 | Login | Recusado | P0 |
| JA-02.05 | Login | Perfil | P1 |
| JA-02.06 | Login | Guarda | P0 |
| JA-02.07 | Logout | Feliz | P1 |
| JA-02.08 | Login | Pós-aprovação | P0 |
| JA-03.01 | Início | Turma | P0 |
| JA-03.02 | Início | Disciplinas | P0 |
| JA-03.03 | Início | Recentes | P1 |
| JA-03.04 | Início | Vazio | P1 |
| JA-03.05 | Início | Escopo | P1 |
| JA-04.01 | Lista | Isolamento | P0 |
| JA-04.02 | Lista | Filtro | P1 |
| JA-04.03 | Lista | UI | P1 |
| JA-05.01 | Detalhe | Feliz | P0 |
| JA-05.02 | Detalhe | Material N | P0 |
| JA-05.03 | Detalhe | Vazio | P1 |
| JA-05.04 | Detalhe | 403 | P0 |
| JA-05.05 | Detalhe | 404 | P1 |
| JA-05.06 | Detalhe | Publicação | P0 |
| JA-05.07 | Detalhe | Navegação | P2 |
| JA-05.08 | Detalhe | XSS/embed | P1 |
| JA-06.01 | Authz | Painel | P0 |
| JA-06.02 | Authz | APIs | P1 |
| JA-06.03 | Authz | Query turma | P0 |
| JA-06.04 | Authz | Arquivo | P0 |
| JA-07.01 | Dados | Fila | P1 |
| JA-07.02 | Dados | XSS | P1 |
| JA-07.03 | Dados | Celular | P2 |
| JA-08.01 | UX | Marca | P1 |
| JA-08.02 | UX | Menu | P1 |
| JA-08.03 | UX | Mobile | P2 |
| JA-09 | E2E | Jornada | P0 |
