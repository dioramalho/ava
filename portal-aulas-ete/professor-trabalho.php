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
            <li class="nav-item"><a class="nav-link active" href="professor-trabalhos.php">Trabalhos</a></li>
            <li class="nav-item"><a class="nav-link" href="professor-alunos.php">Alunos</a></li>
            <li class="nav-item ms-lg-2"><button id="logoutBtn" class="btn btn-sm btn-outline-light btn-touch">Sair</button></li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="container-xxl py-4 py-md-5">
      <section class="ete-page-hero mb-4" id="cadastroHero">
        <h1 class="h3 fw-bold mb-2">Novo trabalho</h1>
        <p class="mb-0" style="opacity: 0.9">Título, data, descrição e, se quiser, um PDF ou um vídeo do YouTube.</p>
      </section>

      <article class="ete-card p-4 p-md-5" id="cadastroCard">
        <form id="trabalhoForm" class="row g-4">
          <div class="col-12">
            <label class="form-label fw-semibold" for="trabalhoTitulo">Título</label>
            <input id="trabalhoTitulo" class="form-control" maxlength="180" required />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="trabalhoTurma">Turma</label>
            <select id="trabalhoTurma" class="form-select" required>
              <option value="">Selecione a turma</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="trabalhoAula">Aula</label>
            <select id="trabalhoAula" class="form-select" required>
              <option value="">Selecione a aula</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="trabalhoData">Data de entrega</label>
            <input id="trabalhoData" class="form-control" type="date" required />
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold" for="trabalhoDescricao">Descrição</label>
            <textarea id="trabalhoDescricao" class="form-control" rows="5" maxlength="10000" required></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="trabalhoArquivo">PDF (opcional)</label>
            <input id="trabalhoArquivo" class="form-control" type="file" accept=".pdf,application/pdf" />
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold" for="trabalhoVideo">Vídeo do YouTube (opcional)</label>
            <input id="trabalhoVideo" class="form-control" type="url" placeholder="https://www.youtube.com/watch?v=..." />
          </div>
          <div class="col-12 d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-ete-primary btn-touch px-4" id="salvarTrabalho">Publicar trabalho</button>
            <a href="professor-trabalhos.php" class="btn btn-outline-secondary btn-touch">Cancelar</a>
          </div>
        </form>
      </article>

      <section class="d-none" id="detalheSection">
        <section class="ete-page-hero mb-4">
          <p id="detalheMeta" class="mb-1 small" style="opacity: 0.85"></p>
          <h1 id="detalheTitulo" class="h3 fw-bold mb-2">Trabalho</h1>
          <p id="detalheData" class="mb-0" style="opacity: 0.9"></p>
        </section>
        <article class="ete-card p-4 mb-4">
          <h2 class="h6 fw-bold text-ete-primary mb-2">Enunciado</h2>
          <p id="detalheDescricao" class="mb-3" style="white-space: pre-wrap"></p>
          <div id="detalheMaterial"></div>
        </article>
        <div id="alunosLista" class="d-flex flex-column gap-3"></div>
        <a href="professor-trabalhos.php" class="btn btn-outline-secondary btn-touch mt-4">Voltar à lista</a>
      </section>
    </main>
    <footer class="ete-footer py-3 text-center small">Escola Técnica Estadual — Portal de Aulas</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/api.js?v=8"></script>
    <script src="js/session.js?v=8"></script>
    <script src="js/professor-trabalho.js?v=1"></script>
  </body>
</html>
