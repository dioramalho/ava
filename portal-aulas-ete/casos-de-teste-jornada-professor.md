# Casos de teste — Jornada do professor (v1)

Plano para validar a jornada definida com o Product Owner: um professor pré-cadastrado entra, vê o painel, cadastra turmas e disciplinas, publica aulas (YouTube + PDF/DOCX) e aprova o aluno vinculando-o a **uma única turma**.

**Fora do escopo v1:** recados, cadastro de professor, aluno em várias turmas, rascunho de aula, reutilizar a mesma aula em N turmas.

**Credencial seed:** login `professor` / senha `1234`.

**Ambiente:** `http://localhost/ava/portal-aulas-ete/` (Apache/XAMPP) ou `php -S` apontando para `portal-aulas-ete`.

O Composer (`portal-aulas-ete/backend`) só afeta o autoload PHP. Sem `vendor/`, a API usa `Autoloader.php`. Este plano não muda por causa do Composer.

**Prioridade:** P0 bloqueia release · P1 fluxo principal · P2 qualidade / borda.

---

## Premissas de produto (oráculo)

| Decisão | Comportamento esperado |
|---|---|
| Disciplina | Catálogo único do professor; vínculo com turma só na aula |
| Aluno | Uma turma; o professor escolhe a turma **ao aprovar** |
| Material | 0..N links YouTube e 0..N arquivos PDF ou DOCX por aula |
| Identificador | ID interno automático; professor informa **código** (ex. TDS-2025) + título |
| Publicação | Salvar aula = já visível para alunos **aprovados** daquela turma |
| Recados | Não aparecem no painel nem no menu do professor |

---

## JP-01 — Login, sessão e perfil

### JP-01.01 — Login válido do professor (P0)

**Objetivo:** o docente pré-cadastrado acessa o painel.

**Pré-condição:** banco com seed; usuário `professor` ativo.

**Passos:**

1. Abrir `index.html` sem cookie de sessão.
2. Conferir marca ETE em destaque no painel azul e formulário “Entrar”.
3. Preencher login `professor` e senha `1234`.
4. Clicar em **Entrar**.

**Esperado:**

- HTTP 200 em `POST /auth/login`.
- Cookie de sessão criado (`session_regenerate_id` no servidor).
- Redirecionamento para `dashboard-professor.html`.
- Menu: Painel, Turmas, Disciplinas, Aulas, Alunos, Sair.
- Não redirecionar para `dashboard.html` (área do aluno).

---

### JP-01.02 — Login com senha inválida (P0)

**Passos:** na tela de login, usar `professor` e senha `errada`.

**Esperado:**

- HTTP 401; mensagem visível (alerta, não só console).
- Permanecer em `index.html`.
- Sem sessão válida (`GET /auth/me` → 401).
- Senha não aparece na URL nem em log de tela.

---

### JP-01.03 — Login e senha vazios (P1)

**Passos:** clicar em **Entrar** com campos em branco (e, em segundo teste, só espaços).

**Esperado:** validação HTML e/ou API 422 “Login e senha são obrigatórios.” Sem sessão.

---

### JP-01.04 — Aluno aprovado não entra no painel do professor (P0)

**Pré-condição:** usuário `perfil=aluno`, `status=aprovado`, com `turma_id`.

**Passos:** autenticar com esse aluno; tentar URL direta `dashboard-professor.html`.

**Esperado:**

- Login ok, destino `dashboard.html`.
- Guarda de sessão: perfil ≠ professor → não permanece no painel docente.
- APIs de professor (`POST /turmas`, aprovação de aluno, criar aula) respondem **403**.

---

### JP-01.05 — Aluno pendente ou recusado não autentica (P0)

**Pré-condição:** aluno `status=pendente` ou `recusado`.

**Passos:** login com e-mail/login e senha cadastrados no pedido.

**Esperado:** 401 (ou mensagem clara de aguardar aprovação). Sem cookie de sessão útil. Não vê aulas.

---

### JP-01.06 — Acesso sem sessão às telas internas (P0)

**Passos:** em janela anônima, abrir:

- `dashboard-professor.html`
- `professor-turmas.html`
- `professor-disciplinas.html`
- `professor-aulas.html`
- `professor-aula.html`
- `professor-alunos.html`

**Esperado:** redirecionar para `index.html`. `GET /auth/me` 401.

---

### JP-01.07 — Logout (P1)

**Passos:** no painel, clicar **Sair**.

**Esperado:** `POST /auth/logout` 200; redireciona para login; voltar no browser não reabre o painel autenticado; `/auth/me` 401.

---

### JP-01.08 — Não existe cadastro público de professor (P1)

**Passos:** na home, procurar “cadastrar professor”; tentar registrar perfil professor via API pública.

**Esperado:** só o link de **solicitar cadastro** do aluno. Sem rota pública de criação de docente.

---

## JP-02 — Painel (dashboard)

### JP-02.01 — Visão geral com dados reais (P0)

**Pré-condição:** professor autenticado; existem turmas, disciplinas, aulas e alunos no banco.

**Passos:** abrir o Painel.

**Esperado:**

- Cards clicáveis: quantidade de **turmas**, **disciplinas**, **aulas**, **alunos ativos** (aprovados).
- Números batem com o banco (não mock estático na versão ligada à API).
- Card de alunos indica pedidos pendentes quando houver.
- Bloco **Solicitações de acesso** em destaque (primeiro bloco visual se houver pendência).
- Lista **Últimas aulas** com título, turma, disciplina e status visível.
- Atalho **Nova aula**.

---

### JP-02.02 — Painel sem pendências (P1)

**Pré-condição:** zero alunos `pendente`.

**Esperado:** fila vazia com mensagem clara; atalho principal permanece **Nova aula**; badge de pendentes = 0 ou oculto.

---

### JP-02.03 — Painel sem turma ou sem disciplina (P0)

**Pré-condição:** catálogo incompleto (0 turmas **ou** 0 disciplinas).

**Passos:** clicar **Nova aula** no painel.

**Esperado:** não abrir formulário “mudo” com selects vazios. Mensagem orientando cadastrar turma **e** disciplina primeiro; ou botão desabilitado com o mesmo texto.

---

### JP-02.04 — Aprovar / recusar a partir do painel (P0)

**Pré-condição:** pelo menos um pendente e duas turmas (TDS-2025 e TDS-2024).

**Passos:**

1. Na fila do painel, escolher turma TDS-2025 para Marina.
2. Clicar **Aprovar**.
3. Em outro pedido, clicar **Recusar** (com ou sem turma selecionada).

**Esperado:**

- Aprovado: some da fila; `status=aprovado`; `turma_id` da turma escolhida; entra em “alunos ativos”; consegue login.
- Recusado: some da fila; `status=recusado`; `turma_id` nulo; não autentica.
- Contadores do painel atualizam sem F5 obrigatório (ou após reload explícito documentado).

---

### JP-02.05 — Recados não aparecem (P1)

**Esperado:** nenhum bloco, menu ou formulário de recado no painel v1.

---

## JP-03 — Turmas

### JP-03.01 — Cadastrar turma válida (P0)

**Passos:**

1. Menu **Turmas**.
2. Código `TDS-2026`, título `Técnico em Desenvolvimento de Sistemas 2026`.
3. **Cadastrar turma**.

**Esperado:**

- ID interno gerado (não digitado pelo professor).
- Registro na lista (código + título).
- Contagem de alunos inicia em 0.
- Aparece no select de aprovação e no select de nova aula.
- Código único.

---

### JP-03.02 — Código e título obrigatórios (P1)

**Passos:** enviar só código, só título, ou ambos vazios/espaços.

**Esperado:** 422 / mensagem de campo obrigatório. Nada gravado.

---

### JP-03.03 — Código duplicado (P1)

**Passos:** cadastrar de novo `TDS-2026`.

**Esperado:** 409 ou equivalente; mensagem de código já existente.

---

### JP-03.04 — Editar turma (P1)

**Passos:** **Editar** TDS-2025; alterar título; salvar. Tentar mudar código para um já usado.

**Esperado:** título atualizado nas listagens, aulas e alunos. Código duplicado recusado. IDs de aulas/alunos não quebram.

---

### JP-03.05 — Disciplina não é criada “dentro” da turma (P1)

**Esperado:** tela de turma não pede disciplina. A mesma disciplina do catálogo pode ser usada em aulas de turmas diferentes.

---

## JP-04 — Disciplinas

### JP-04.01 — Cadastrar disciplina no catálogo (P0)

**Passos:** código `POO`, título `Programação Orientada a Objetos`; salvar.

**Esperado:** aparece no catálogo uma única vez; disponível para qualquer turma na hora de criar aula.

---

### JP-04.02 — Código duplicado de disciplina (P1)

**Esperado:** recusa (409). Não criar “POO da turma A” e “POO da turma B” como dois cadastros.

---

### JP-04.03 — Campos obrigatórios (P1)

**Esperado:** código e título obrigatórios; ID interno automático.

---

## JP-05 — Aulas (lista, vínculo, publicação)

### JP-05.01 — Listar e filtrar (P1)

**Pré-condição:** aulas em turmas e disciplinas distintas.

**Passos:** em **Aulas**, filtrar por turma TDS-2025; depois por disciplina Programação Web; depois ambos.

**Esperado:** lista só as combinações corretas. Colunas: título, turma, disciplina, resumo de material (N vídeos · N arquivos), editar.

---

### JP-05.02 — Publicar aula completa (P0) — caminho feliz

**Pré-condição:** ≥1 turma e ≥1 disciplina.

**Passos:**

1. **Nova aula**.
2. Título `Introdução a HTML`.
3. Turma TDS-2025, disciplina Programação Web.
4. Um link YouTube válido (`https://www.youtube.com/watch?v=dQw4w9WgXcQ` ou URL da escola).
5. Um PDF válido.
6. **Salvar e publicar**.

**Esperado:**

- ID da aula gerado pelo sistema.
- Registro visível na lista imediatamente.
- Aluno **aprovado em TDS-2025** vê a aula (título + vídeo + PDF).
- Aluno aprovado em **outra** turma **não** vê essa aula.
- Não existe passo extra “Publicar”.

---

### JP-05.03 — Título, turma e disciplina obrigatórios (P0)

**Passos:** salvar sem título; sem turma; sem disciplina.

**Esperado:** não grava. Mensagem por campo. Material sozinho não cria aula.

---

### JP-05.04 — Aula sem material (P1)

**Passos:** só título + turma + disciplina; salvar.

**Esperado (v1):** permitido (0 vídeos e 0 arquivos). Aluno vê a aula “vazia” de anexos. Documentar se o PO preferir exigir pelo menos um material — hoje a regra é 0..N.

---

### JP-05.05 — Vários vídeos e vários arquivos (P0)

**Passos:** dois links YouTube + um PDF + um DOCX na mesma aula.

**Esperado:** todos persistem na ordem informada; aluno vê os dois vídeos e baixa os dois arquivos.

---

### JP-05.06 — Bloqueio se faltar turma ou disciplina (P0)

**Pré-condição:** apagar/não haver turmas **ou** disciplinas.

**Esperado:** **Nova aula** bloqueada com texto claro (“Cadastre ao menos uma turma e uma disciplina”). Não enviar POST.

---

### JP-05.07 — Editar aula (P1)

**Passos:** editar título, trocar disciplina, adicionar vídeo, manter arquivos.

**Esperado:** alterações visíveis na hora para o aluno da turma. Trocar a **turma** da aula faz a aula sumir da turma antiga e aparecer na nova (v1: uma aula = uma turma).

---

### JP-05.08 — Cancelar (P2)

**Passos:** preencher o formulário e **Cancelar**.

**Esperado:** volta para lista; nada persistido.

---

## JP-06 — Material (YouTube, PDF, DOCX)

### JP-06.01 — URL YouTube válida (P0)

Aceitar, no mínimo:

- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`

**Esperado:** grava; reproduzível no lado do aluno (embed ou link).

---

### JP-06.02 — URL inválida ou não-YouTube (P1)

**Passos:** `https://exemplo.com/aula` e `nao-e-url`.

**Esperado:** recusa com mensagem. Campos vazios de vídeo extras são ignorados (não geram erro).

---

### JP-06.03 — Tipos de arquivo aceitos (P0)

**Passos:** enviar `.pdf` e `.docx` (Office Open XML).

**Esperado:** upload ok; nomes originais preservados na listagem; arquivo gravado em storage com nome único.

---

### JP-06.04 — Tipos recusados (P0)

**Passos:** `.exe`, `.zip`, `.doc` (binário antigo), `.png`.

**Esperado:** 422; nenhum arquivo perigoso em `storage/uploads`. Mensagem “apenas PDF ou DOCX”.

---

### JP-06.05 — Arquivo vazio ou acima do limite (P2)

**Esperado:** recusa controlada (não 500 HTML). Se houver limite de tamanho, a mensagem cita o limite.

---

### JP-06.06 — Adicionar outro campo de vídeo (P2)

**Passos:** clicar **+ Adicionar outro vídeo**; preencher o novo campo.

**Esperado:** linha extra no formulário; todos os URLs não vazios são salvos.

---

## JP-07 — Alunos (aprovação e uma turma)

### JP-07.01 — Pedido aparece para o professor (P0)

**Pré-condição:** aluno enviou `cadastro-aluno.html` (nome, celular, e-mail, senha).

**Esperado:**

- Usuário `perfil=aluno`, `status=pendente`, `turma_id` nulo.
- Painel e **Alunos** listam nome, e-mail, celular.
- Aluno ainda **não** escolheu turma no cadastro.

---

### JP-07.02 — Aprovar com turma obrigatória (P0)

**Passos:** **Aprovar** sem selecionar turma (select vazio, se existir). Depois aprovar com turma válida.

**Esperado:**

- Sem turma: não aprova; mensagem “escolha a turma”.
- Com turma: `aprovado` + exatamente um `turma_id`. Login do aluno passa a funcionar. Lista “Alunos com acesso” mostra a turma.

---

### JP-07.03 — Recusar pedido (P0)

**Esperado:** `recusado`; some dos pendentes; não entra em “com acesso”; login bloqueado; não consome vaga na contagem da turma.

---

### JP-07.04 — Aluno não pode ter duas turmas (P0)

**Passos:** após aprovado em TDS-2025, tentar (UI ou API) atribuir também TDS-2024.

**Esperado:** v1 não oferece multi-turma. API rejeita segundo vínculo. UI não tem “adicionar outra turma”.

---

### JP-07.05 — Aprovar de novo o mesmo pedido (P1)

**Passos:** interceptar e reenviar aprovação de id já aprovado/recusado.

**Esperado:** não altera indevidamente (só pendente muda). 404/409/422.

---

### JP-07.06 — Sem turmas cadastradas, não dá para aprovar (P1)

**Esperado:** mensagem para cadastrar turma antes; botão Aprovar desabilitado ou erro claro.

---

### JP-07.07 — Aluno vê só aulas da própria turma (P0)

**Pré-condição:** aula A em TDS-2025, aula B em TDS-2024; aluno só em TDS-2025.

**Esperado:** dashboard do aluno lista A e não B.

---

## JP-08 — Segurança e API (professor)

### JP-08.01 — Rotas docentes exigem professor (P0)

Sem sessão: **401**. Sessão aluno: **403**.

Cobrir no mínimo: criar/editar turma, disciplina, aula, upload, listar pendentes, aprovar, recusar.

---

### JP-08.02 — SQL / XSS básico (P1)

**Passos:** título de aula `<script>alert(1)</script>` e código de turma `TDS-2025' OR '1'='1`.

**Esperado:** persistido como texto ou sanitizado; sem script executado no painel/aluno; consulta PDO com bind (não concatenar SQL).

---

### JP-08.03 — Upload não sobrescreve arquivo de outra aula (P1)

**Esperado:** nome interno único (`uniqid` ou equivalente); dois PDFs com o mesmo nome original convivem.

---

## JP-09 — Navegação e identidade

### JP-09.01 — Menu da jornada (P1)

**Esperado:** Painel · Turmas · Disciplinas · Aulas · Alunos · Sair. Item ativo destacado. Logo ETE na navbar com fundo branco. Faixa amarelo/verde/vermelho no topo.

---

### JP-09.02 — Responsivo essencial (P2)

**Passos:** viewport ~375px e ~1440px nas telas de painel, nova aula e alunos.

**Esperado:** botões tocáveis (≥44px); formulários usáveis; menu colapsa no mobile.

---

## JP-10 — Jornada ponta a ponta (P0)

Simula o professor no primeiro uso, na ordem de trabalho acordada.

| Passo | Ação | Critério de aceite |
|---|---|---|
| 1 | Login `professor` / `1234` | Painel docente |
| 2 | Cadastrar turma TDS-2026 | Lista + ID interno |
| 3 | Cadastrar disciplina WEB | Catálogo único |
| 4 | Tentar nova aula antes disso (opcional, ambiente vazio) | Bloqueio se faltar 2 ou 3 |
| 5 | Publicar aula com 2 YouTube + PDF + DOCX | Visível na lista |
| 6 | Aluno solicita acesso | Aparece na fila |
| 7 | Aprovar na TDS-2026 | Aluno loga |
| 8 | Aluno abre o dashboard | Vê só a aula da TDS-2026 |
| 9 | Recusar outro pedido | Esse aluno não entra |
| 10 | Sair | Volta ao login |

**Falha em qualquer passo P0 desta tabela = jornada v1 não está pronta.**

---

## Matriz rápida

| ID | Módulo | Tipo | P |
|---|---|---|---|
| JP-01.01 | Login | Feliz | P0 |
| JP-01.02 | Login | Negativo | P0 |
| JP-01.03 | Login | Validação | P1 |
| JP-01.04 | Sessão | Autorização | P0 |
| JP-01.05 | Sessão | Autorização | P0 |
| JP-01.06 | Sessão | Guarda | P0 |
| JP-01.07 | Logout | Feliz | P1 |
| JP-01.08 | Perfil | Regra v1 | P1 |
| JP-02.01 | Painel | Feliz | P0 |
| JP-02.02 | Painel | Vazio | P1 |
| JP-02.03 | Painel | Bloqueio | P0 |
| JP-02.04 | Painel | Aprovação | P0 |
| JP-02.05 | Painel | Fora de escopo | P1 |
| JP-03.01 | Turmas | Feliz | P0 |
| JP-03.02 | Turmas | Validação | P1 |
| JP-03.03 | Turmas | Unicidade | P1 |
| JP-03.04 | Turmas | Edição | P1 |
| JP-03.05 | Turmas | Modelo | P1 |
| JP-04.01 | Disciplinas | Feliz | P0 |
| JP-04.02 | Disciplinas | Unicidade | P1 |
| JP-04.03 | Disciplinas | Validação | P1 |
| JP-05.01 | Aulas | Filtro | P1 |
| JP-05.02 | Aulas | Feliz | P0 |
| JP-05.03 | Aulas | Validação | P0 |
| JP-05.04 | Aulas | Borda | P1 |
| JP-05.05 | Aulas | Material N | P0 |
| JP-05.06 | Aulas | Bloqueio | P0 |
| JP-05.07 | Aulas | Edição | P1 |
| JP-05.08 | Aulas | Cancelar | P2 |
| JP-06.01 | Material | YouTube | P0 |
| JP-06.02 | Material | URL inválida | P1 |
| JP-06.03 | Material | PDF/DOCX | P0 |
| JP-06.04 | Material | Tipo inválido | P0 |
| JP-06.05 | Material | Tamanho | P2 |
| JP-06.06 | Material | UI | P2 |
| JP-07.01 | Alunos | Fila | P0 |
| JP-07.02 | Alunos | Aprovar | P0 |
| JP-07.03 | Alunos | Recusar | P0 |
| JP-07.04 | Alunos | 1 turma | P0 |
| JP-07.05 | Alunos | Idempotência | P1 |
| JP-07.06 | Alunos | Sem turma | P1 |
| JP-07.07 | Alunos | Visibilidade | P0 |
| JP-08.01 | API | 401/403 | P0 |
| JP-08.02 | API | Injeção | P1 |
| JP-08.03 | API | Upload | P1 |
| JP-09.01 | UX | Nav/marca | P1 |
| JP-09.02 | UX | Mobile | P2 |
| JP-10 | E2E | Jornada | P0 |
