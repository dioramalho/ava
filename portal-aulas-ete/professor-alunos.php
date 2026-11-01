<?php include 'shared/header-professor.php'; ?>
  <body class="ete-app-shell">
    <div class="ete-stripes"><span class="s-y"></span><span class="s-g"></span><span class="s-r"></span></div>
    <nav class="navbar navbar-expand-lg ete-navbar sticky-top">
      <div class="container-xxl">
        <a class="navbar-brand d-flex align-items-center gap-3" href="dashboard-professor.html">
          <img src="assets/logo-ete.png" alt="ETE — Escola Técnica Estadual" class="ete-logo-nav" />
          <span class="d-none d-md-block fw-bold">Portal de Aulas</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navProf" aria-controls="navProf" aria-expanded="false" aria-label="Abrir menu">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navProf">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-1 mt-3 mt-lg-0">
            <li class="nav-item"><a class="nav-link" href="dashboard-professor.html">Painel</a></li>
            <li class="nav-item"><a class="nav-link" href="professor-turmas.php">Turmas</a></li>
            <li class="nav-item"><a class="nav-link" href="professor-disciplinas.php">Disciplinas</a></li>
            <li class="nav-item"><a class="nav-link" href="professor-aulas.php">Aulas</a></li>
            <li class="nav-item"><a class="nav-link" href="professor-trabalhos.php">Trabalhos</a></li>
            <li class="nav-item"><a class="nav-link active" href="professor-alunos.php">Alunos</a></li>
            <li class="nav-item ms-lg-2"><button id="logoutBtn" class="btn btn-sm btn-outline-light btn-touch">Sair</button></li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="container-xxl py-4 py-md-5">
      <section class="ete-page-hero mb-4">
        <h1 class="h3 fw-bold mb-2">Alunos</h1>
        <p class="mb-0" style="opacity: 0.9">Na v1 cada aluno pertence a uma única turma. Aprove o pedido e escolha a turma no mesmo passo.</p>
      </section>

      <section class="mb-4">
        <article class="ete-card p-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 fw-bold mb-0">Pedidos pendentes</h2>
            <span class="badge ete-badge-pending" id="badgePendentes">0</span>
          </div>
          <div class="d-grid gap-3" id="pendentesWrap">
            <p class="text-muted mb-0">Carregando...</p>
          </div>
        </article>
      </section>

      <section>
        <article class="ete-card p-4">
          <h2 class="h5 fw-bold mb-3">Alunos com acesso</h2>
          <form class="row g-2 mb-3" id="filtrosAlunos" role="search">
            <div class="col-md-4 col-lg-3">
              <label class="form-label small mb-1" for="filtroTurma">Turma</label>
              <select class="form-select" id="filtroTurma" aria-label="Filtrar por turma">
                <option value="">Todas as turmas</option>
              </select>
            </div>
            <div class="col-md-5 col-lg-6">
              <label class="form-label small mb-1" for="filtroTermo">Aluno</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="search" class="form-control" id="filtroTermo" placeholder="Buscar por nome, e-mail ou login" aria-label="Buscar alunos" />
              </div>
            </div>
            <div class="col-md-3 col-lg-3 d-grid">
              <label class="form-label small mb-1 d-none d-md-block" aria-hidden="true">&nbsp;</label>
              <button type="button" class="btn btn-outline-secondary btn-touch" id="limparFiltros">Limpar filtros</button>
            </div>
          </form>
          <div class="table-responsive">
            <table class="table ete-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>Contato</th>
                  <th>Turma</th>
                  <th>Status</th>
                  <th></th>
                </tr>
              </thead>
              <tbody id="alunosTableBody">
                <tr><td colspan="5" class="text-muted">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
          <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3" id="alunosPaginacao">
            <p class="small text-muted mb-0" id="alunosResumo"></p>
            <nav aria-label="Paginação de alunos">
              <ul class="pagination pagination-sm mb-0" id="alunosPaginas"></ul>
            </nav>
          </div>
        </article>
      </section>
    </main>

    <div class="modal fade" id="alunoModal" tabindex="-1" aria-labelledby="alunoModalTitulo" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="alunoForm" novalidate>
          <div class="modal-header">
            <h2 class="modal-title h5 fw-bold" id="alunoModalTitulo">Editar aluno</h2>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body d-grid gap-3">
            <input type="hidden" id="alunoId" />
            <div>
              <label class="form-label fw-semibold" for="alunoNome">Nome</label>
              <input id="alunoNome" class="form-control" required />
            </div>
            <div>
              <label class="form-label fw-semibold" for="alunoEmail">E-mail (login)</label>
              <input id="alunoEmail" type="email" class="form-control" required maxlength="120" autocapitalize="none" spellcheck="false" placeholder="nome.sobrenome@escola.com" />
            </div>
            <div>
              <label class="form-label fw-semibold" for="alunoCelular">Celular</label>
              <input id="alunoCelular" type="tel" class="form-control" inputmode="numeric" maxlength="20" autocomplete="tel" placeholder="(81)99794-1373" />
            </div>
            <div>
              <label class="form-label fw-semibold" for="alunoTurma">Turma</label>
              <select id="alunoTurma" class="form-select" required></select>
            </div>
            <div>
              <label class="form-label fw-semibold" for="alunoStatus">Status</label>
              <select id="alunoStatus" class="form-select" required>
                <option value="aprovado">Aprovado</option>
                <option value="recusado">Recusado</option>
                <option value="pendente">Pendente</option>
              </select>
            </div>
            <div class="border rounded p-3">
              <p class="small text-muted mb-2 mb-md-3">Redefine a senha do aluno para a senha padrão da escola.</p>
              <button type="button" class="btn btn-outline-warning btn-touch" id="alunoResetSenha">Redefinir senha para @ETE2626</button>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-touch" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-ete-primary btn-touch" id="alunoSubmit">Salvar alterações</button>
          </div>
        </form>
      </div>
    </div>
    <footer class="ete-footer py-3 text-center small">Escola Técnica Estadual — Portal de Aulas</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/api.js?v=7"></script>
    <script src="js/session.js?v=6"></script>
    <script src="js/aluno-form-masks.js?v=1"></script>
    <script src="js/professor-alunos.js?v=9"></script>
  </body>
</html>
