(function () {
  if (!window.isCurrentPortalFile('aluno-trabalhos.html')) {
    return;
  }

  const lista = document.getElementById('trabalhosLista');

  function formatarData(valor) {
    const texto = String(valor || '').slice(0, 10);
    const partes = texto.split('-');
    if (partes.length !== 3 || partes[0].length !== 4) {
      return texto;
    }
    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }

  function rotuloStatus(status) {
    if (status === 'entregue') {
      return 'Entregue';
    }
    if (status === 'entregue_atraso') {
      return 'Entregue com atraso';
    }
    return 'Pendente';
  }

  function classeStatus(status) {
    if (status === 'entregue') {
      return 'text-bg-success';
    }
    if (status === 'entregue_atraso') {
      return 'text-bg-danger';
    }
    return 'text-bg-warning';
  }

  async function carregar() {
    const session = await window.validatePortalSession({ requiredProfile: 'aluno' });
    if (!session) {
      return;
    }

    const response = await apiRequest('/aluno/trabalhos');
    const trabalhos = response.data || [];
    if (!trabalhos.length) {
      lista.innerHTML = '<article class="ete-card p-4"><p class="text-muted mb-0">Nenhum trabalho publicado para a sua turma.</p></article>';
      return;
    }

    lista.innerHTML = trabalhos.map(function (item) {
      return '<article class="ete-card p-4 mb-3">' +
        '<div class="d-flex flex-column flex-md-row justify-content-between gap-3">' +
        '<div><p class="small text-muted mb-1">' + escapeHtml(item.disciplina_titulo || '') + ' · ' + escapeHtml(item.aula_titulo || '') + '</p>' +
        '<h2 class="h5 fw-bold mb-1">' + escapeHtml(item.titulo) + '</h2>' +
        '<p class="mb-0">Entrega em ' + escapeHtml(formatarData(item.data_entrega_vigente)) + '</p></div>' +
        '<div class="d-flex flex-column align-items-md-end gap-2">' +
        '<span class="badge ' + classeStatus(item.status) + '">' + rotuloStatus(item.status) + '</span>' +
        '<a class="btn btn-sm btn-ete-primary" href="aluno-trabalho.html?id=' + encodeURIComponent(item.id) + '">Abrir</a></div></div></article>';
    }).join('');
  }

  carregar().catch(function (error) {
    lista.innerHTML = '<div class="alert alert-danger">' + escapeHtml(error.message) + '</div>';
  });
})();
