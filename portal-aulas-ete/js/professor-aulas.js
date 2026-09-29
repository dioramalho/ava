(function () {
  if (!window.isCurrentPortalFile('professor-aulas.php')) {
    return;
  }

  const filtroTurma = document.getElementById('filtroTurma');
  const filtroDisciplina = document.getElementById('filtroDisciplina');
  const filtroTermo = document.getElementById('filtroTermo');
  const filtrosForm = document.getElementById('filtrosAulas');
  const limparFiltros = document.getElementById('limparFiltros');
  const tabela = document.getElementById('aulasTableBody');

  let buscaTimer = null;
  let ultimaRequisicao = 0;

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    try {
      const lists = await Promise.all([apiRequest('/turmas'), apiRequest('/disciplinas')]);
      fillSelect(filtroTurma, lists[0].data || [], 'Todas as turmas');
      fillSelect(filtroDisciplina, lists[1].data || [], 'Todas as disciplinas');

      filtroTurma.addEventListener('change', load);
      filtroDisciplina.addEventListener('change', load);
      filtroTermo.addEventListener('input', function () {
        clearTimeout(buscaTimer);
        buscaTimer = setTimeout(load, 300);
      });
      filtrosForm.addEventListener('submit', function (event) {
        event.preventDefault();
        clearTimeout(buscaTimer);
        load();
      });
      limparFiltros.addEventListener('click', function () {
        clearTimeout(buscaTimer);
        filtrosForm.reset();
        load();
      });
      tabela.addEventListener('click', onTableClick);
      await load();
    } catch (error) {
      tabela.innerHTML = '<tr><td colspan="5" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
    }
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
    const termo = filtroTermo.value.trim();
    if (termo) {
      query.push('termo=' + encodeURIComponent(termo));
    }
    let route = '/aulas';
    if (query.length) {
      route += '?' + query.join('&');
    }

    const requisicao = ++ultimaRequisicao;
    let response;
    try {
      response = await apiRequest(route);
    } catch (error) {
      if (requisicao === ultimaRequisicao) {
        tabela.innerHTML = '<tr><td colspan="5" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
      }
      return;
    }
    if (requisicao !== ultimaRequisicao) {
      return;
    }
    const aulas = response.data || [];

    if (!aulas.length) {
      const mensagem = query.length ? 'Nenhuma aula encontrada com os filtros informados.' : 'Nenhuma aula publicada.';
      tabela.innerHTML = '<tr><td colspan="5" class="text-muted">' + mensagem + '</td></tr>';
      return;
    }

    tabela.innerHTML = aulas.map(function (aula) {
      return '<tr>' +
        '<td><strong>' + escapeHtml(aula.titulo) + '</strong></td>' +
        '<td>' + escapeHtml(aula.turma_codigo) + '</td>' +
        '<td>' + escapeHtml(aula.disciplina_titulo) + '</td>' +
        '<td><span class="small">' + aula.videos_count + ' vídeo(s) · ' + aula.arquivos_count + ' arquivo(s)</span></td>' +
        '<td class="text-end text-nowrap">' +
        '<a class="btn btn-sm btn-outline-secondary me-1" href="professor-aula.php?id=' + aula.id + '">Editar</a>' +
        '<button type="button" class="btn btn-sm btn-outline-danger js-excluir-aula" data-id="' + aula.id + '" data-titulo="' + escapeHtml(aula.titulo) + '">Excluir</button>' +
        '</td>' +
        '</tr>';
    }).join('');
  }

  async function onTableClick(event) {
    const button = event.target.closest ? event.target.closest('.js-excluir-aula') : null;
    if (!button) {
      return;
    }

    const id = button.getAttribute('data-id');
    const titulo = button.getAttribute('data-titulo') || 'esta aula';
    if (!window.confirm('Excluir a aula "' + titulo + '"? Os arquivos enviados também serão removidos.')) {
      return;
    }

    button.disabled = true;
    try {
      await apiRequest('/aulas/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
      });
      await load();
    } catch (error) {
      button.disabled = false;
      window.alert(error.message);
    }
  }
})();
