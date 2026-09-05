(function () {
  if (!window.location.pathname.endsWith('professor-aulas.html')) {
    return;
  }

  const filtroTurma = document.getElementById('filtroTurma');
  const filtroDisciplina = document.getElementById('filtroDisciplina');
  const tabela = document.getElementById('aulasTableBody');

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    const lists = await Promise.all([apiRequest('/turmas'), apiRequest('/disciplinas')]);
    fillSelect(filtroTurma, lists[0].data || [], 'Todas as turmas');
    fillSelect(filtroDisciplina, lists[1].data || [], 'Todas as disciplinas');

    filtroTurma.addEventListener('change', load);
    filtroDisciplina.addEventListener('change', load);
    await load();
  }

  function fillSelect(select, items, emptyLabel) {
    select.innerHTML = '<option value="">' + emptyLabel + '</option>' + items.map(function (item) {
      return '<option value="' + item.id + '">' + escapeHtml(item.codigo) + ' — ' + escapeHtml(item.titulo) + '</option>';
    }).join('');
  }

  async function load() {
    const query = [];
    if (filtroTurma.value) {
      query.push('turma_id=' + encodeURIComponent(filtroTurma.value));
    }
    if (filtroDisciplina.value) {
      query.push('disciplina_id=' + encodeURIComponent(filtroDisciplina.value));
    }
    let route = '/aulas';
    if (query.length) {
      route += '?' + query.join('&');
    }
    const response = await apiRequest(route);
    const aulas = response.data || [];

    if (!aulas.length) {
      tabela.innerHTML = '<tr><td colspan="5" class="text-muted">Nenhuma aula publicada.</td></tr>';
      return;
    }

    tabela.innerHTML = aulas.map(function (aula) {
      return '<tr>' +
        '<td><strong>' + escapeHtml(aula.titulo) + '</strong></td>' +
        '<td>' + escapeHtml(aula.turma_codigo) + '</td>' +
        '<td>' + escapeHtml(aula.disciplina_titulo) + '</td>' +
        '<td><span class="small">' + aula.videos_count + ' vídeo(s) · ' + aula.arquivos_count + ' arquivo(s)</span></td>' +
        '<td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="professor-aula.html?id=' + aula.id + '">Editar</a></td>' +
        '</tr>';
    }).join('');
  }
})();
