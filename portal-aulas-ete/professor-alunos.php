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
          <div class="table-responsive">
            <table class="table ete-table align-middle mb-0">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>Contato</th>
                  <th>Turma</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="alunosTableBody">
                <tr><td colspan="4" class="text-muted">Carregando...</td></tr>
              </tbody>
            </table>
          </div>
        </article>
      </section>
    </main>
    <footer class="ete-footer py-3 text-center small">Escola Técnica Estadual — Portal de Aulas</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/api.js?v=3"></script>
    <script src="js/session.js?v=3"></script>
    <script src="js/professor-alunos.js?v=3"></script>
  </body>
</html>
