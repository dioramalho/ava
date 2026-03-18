(function () {
  const isStudentPage = window.location.pathname.endsWith('dashboard.html');
  if (!isStudentPage) {
    return;
  }

  const recadosList = document.getElementById('recadosList');
  const disciplinasGrid = document.getElementById('disciplinasGrid');

  if (!recadosList || !disciplinasGrid) {
    return;
  }

  initialize();

  async function initialize() {
    try {
      const session = await window.validatePortalSession();
      if (!session) {
        return;
      }

      const results = await Promise.all([
        apiRequest('/recados'),
        apiRequest('/documentos')
      ]);

      renderRecados(results[0].data || []);
      renderDisciplinas(results[1].data || []);
    } catch (error) {
      renderError(error.message || 'Não foi possível carregar as informações do portal.');
    }
  }

  function renderRecados(recados) {
    if (!recados.length) {
      recadosList.innerHTML = '<li class="list-group-item px-0 text-muted">Nenhum recado disponível.</li>';
      return;
    }

    recadosList.innerHTML = recados.map(function (recado) {
      return '<li class="list-group-item px-0">• <strong>' + recado.titulo + ':</strong> ' + recado.mensagem + ' <span class="text-muted d-block small mt-1">Turma: ' + recado.turma_nome + '</span></li>';
    }).join('');
  }

  function renderDisciplinas(documentos) {
    if (!documentos.length) {
      disciplinasGrid.innerHTML = '<div class="col-12"><div class="alert alert-light border">Nenhum documento disponível no momento.</div></div>';
      return;
    }

    const grouped = {};

    documentos.forEach(function (documento) {
      const key = documento.materia_nome + '||' + documento.turma_nome;

      if (!grouped[key]) {
        grouped[key] = {
          materia_nome: documento.materia_nome,
          turma_nome: documento.turma_nome,
          documentos: []
        };
      }

      grouped[key].documentos.push(documento);
    });

    disciplinasGrid.innerHTML = Object.keys(grouped).map(function (key) {
      const group = grouped[key];
      const links = group.documentos.map(function (documento) {
        return '<li><a class="btn btn-outline-primary btn-touch w-100 text-start" href="' + documento.caminho_arquivo + '" target="_blank"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>' + documento.titulo + '.pdf</a></li>';
      }).join('');

      return '<div class="col-12 col-md-6 col-xl-4">' +
        '<article class="card h-100 border-0 shadow-sm rounded-4 subject-card">' +
          '<div class="card-body d-flex flex-column p-4">' +
            '<h3 class="h5 fw-bold mb-1">' + group.materia_nome + '</h3>' +
            '<p class="text-muted mb-3">Turma ' + group.turma_nome + '</p>' +
            '<ul class="list-unstyled d-grid gap-2 mb-0 mt-auto">' + links + '</ul>' +
          '</div>' +
        '</article>' +
      '</div>';
    }).join('');
  }

  function renderError(message) {
    recadosList.innerHTML = '<li class="list-group-item px-0 text-danger">' + message + '</li>';
    disciplinasGrid.innerHTML = '<div class="col-12"><div class="alert alert-danger">' + message + '</div></div>';
  }
})();
