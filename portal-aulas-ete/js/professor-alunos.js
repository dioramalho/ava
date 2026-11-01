(function () {
  if (!window.isCurrentPortalFile('professor-alunos.php')) {
    return;
  }

  const PAGE_SIZE = 10;

  const pendentesWrap = document.getElementById('pendentesWrap');
  const tabela = document.getElementById('alunosTableBody');
  const badgePendentes = document.getElementById('badgePendentes');
  const filtroTurma = document.getElementById('filtroTurma');
  const filtroTermo = document.getElementById('filtroTermo');
  const filtrosForm = document.getElementById('filtrosAlunos');
  const limparFiltros = document.getElementById('limparFiltros');
  const alunosResumo = document.getElementById('alunosResumo');
  const alunosPaginas = document.getElementById('alunosPaginas');
  const modalEl = document.getElementById('alunoModal');
  const form = document.getElementById('alunoForm');
  const botaoSalvar = document.getElementById('alunoSubmit');
  const campos = {
    id: document.getElementById('alunoId'),
    nome: document.getElementById('alunoNome'),
    email: document.getElementById('alunoEmail'),
    celular: document.getElementById('alunoCelular'),
    turma: document.getElementById('alunoTurma'),
    status: document.getElementById('alunoStatus')
  };
  const botaoResetSenha = document.getElementById('alunoResetSenha');
  const masks = window.PortalAlunoMasks || null;

  const state = {
    turmas: [],
    alunos: [],
    pagina: 1
  };

  let buscaTimer = null;

  let modal = null;

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    if (window.bootstrap && modalEl) {
      modal = new window.bootstrap.Modal(modalEl);
    }
    form.addEventListener('submit', onSubmitEdicao);
    tabela.addEventListener('click', onTableClick);
    if (botaoResetSenha) {
      botaoResetSenha.addEventListener('click', onResetSenha);
    }
    if (masks && campos.celular) {
      campos.celular.addEventListener('input', function () {
        campos.celular.value = masks.maskCelular(campos.celular.value);
      });
    }
    if (masks && campos.email) {
      campos.email.addEventListener('input', function () {
        var next = masks.maskEmail(campos.email.value);
        if (campos.email.value !== next) {
          campos.email.value = next;
        }
      });
    }

    if (filtroTurma) {
      filtroTurma.addEventListener('change', function () {
        state.pagina = 1;
        renderLista();
      });
    }
    if (filtroTermo) {
      filtroTermo.addEventListener('input', function () {
        clearTimeout(buscaTimer);
        buscaTimer = setTimeout(function () {
          state.pagina = 1;
          renderLista();
        }, 300);
      });
    }
    if (filtrosForm) {
      filtrosForm.addEventListener('submit', function (event) {
        event.preventDefault();
        clearTimeout(buscaTimer);
        state.pagina = 1;
        renderLista();
      });
    }
    if (limparFiltros) {
      limparFiltros.addEventListener('click', function () {
        clearTimeout(buscaTimer);
        if (filtrosForm) {
          filtrosForm.reset();
        }
        state.pagina = 1;
        renderLista();
      });
    }
    if (alunosPaginas) {
      alunosPaginas.addEventListener('click', function (event) {
        const botao = event.target.closest ? event.target.closest('.js-pagina') : null;
        if (!botao || botao.disabled) {
          return;
        }
        state.pagina = parseInt(botao.getAttribute('data-pagina'), 10) || 1;
        renderLista();
      });
    }

    try {
      const results = await Promise.all([
        apiRequest('/alunos/pendentes'),
        apiRequest('/alunos'),
        apiRequest('/turmas')
      ]);
      state.turmas = results[2].data || [];
      preencherFiltroTurmas(state.turmas);
      renderPendentes(results[0].data || [], state.turmas);
      renderAprovados(results[1].data || []);
    } catch (error) {
      pendentesWrap.innerHTML = '<p class="text-danger mb-0">' + escapeHtml(error.message) + '</p>';
      tabela.innerHTML = '<tr><td colspan="5" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
      limparPaginacao();
    }
  }

  function renderPendentes(pendentes, turmas) {
    if (badgePendentes) {
      badgePendentes.textContent = String(pendentes.length);
    }
    if (!pendentes.length) {
      pendentesWrap.innerHTML = '<p class="text-muted mb-0">Nenhum pedido pendente.</p>';
      return;
    }

    const options = turmaOptions(turmas);

    pendentesWrap.innerHTML = pendentes.map(function (aluno) {
      return '<div class="ete-card ete-request-card p-3" data-aluno-id="' + aluno.id + '">' +
        '<div class="row g-3 align-items-end">' +
        '<div class="col-lg-5"><strong>' + escapeHtml(aluno.nome) + '</strong>' +
        '<p class="small text-muted mb-0">' + escapeHtml(aluno.email) + ' · ' + escapeHtml(aluno.celular || '') + '</p></div>' +
        '<div class="col-lg-4"><label class="form-label small mb-1">Turma</label>' +
        '<select class="form-select js-turma">' + options + '</select></div>' +
        '<div class="col-lg-3 d-flex gap-2">' +
        '<button type="button" class="btn btn-ete-green btn-touch flex-fill js-aprovar">Aprovar</button>' +
        '<button type="button" class="btn btn-outline-danger btn-touch js-recusar">Recusar</button>' +
        '</div></div></div>';
    }).join('');

    pendentesWrap.querySelectorAll('[data-aluno-id]').forEach(function (card) {
      const id = card.getAttribute('data-aluno-id');
      card.querySelector('.js-aprovar').addEventListener('click', async function () {
        try {
          await apiRequest('/alunos/aprovar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, turma_id: card.querySelector('.js-turma').value })
          });
          window.location.reload();
        } catch (error) {
          window.alert(error.message);
        }
      });
      card.querySelector('.js-recusar').addEventListener('click', async function () {
        try {
          await apiRequest('/alunos/recusar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
          });
          window.location.reload();
        } catch (error) {
          window.alert(error.message);
        }
      });
    });
  }

  function turmaOptions(turmas) {
    return turmas.map(function (turma) {
      return '<option value="' + turma.id + '">' + escapeHtml(turma.codigo) + ' — ' + escapeHtml(turma.titulo) + '</option>';
    }).join('');
  }

  function preencherFiltroTurmas(turmas) {
    if (!filtroTurma) {
      return;
    }
    const atual = filtroTurma.value;
    filtroTurma.innerHTML = '<option value="">Todas as turmas</option>' + turmas.map(function (turma) {
      return '<option value="' + turma.id + '">' + escapeHtml(turma.codigo) + ' — ' + escapeHtml(turma.titulo) + '</option>';
    }).join('');
    if (atual) {
      filtroTurma.value = atual;
    }
  }

  function renderAprovados(alunos) {
    state.alunos = alunos || [];
    renderLista();
  }

  function filtrosAtivos() {
    return !!((filtroTurma && filtroTurma.value) || (filtroTermo && filtroTermo.value.trim()));
  }

  function normalizarBusca(value) {
    var texto = String(value || '').toLowerCase();
    if (texto.normalize) {
      texto = texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }
    return texto;
  }

  function alunosFiltrados() {
    const turmaId = filtroTurma ? filtroTurma.value : '';
    const termo = normalizarBusca(filtroTermo ? filtroTermo.value.trim() : '');

    return state.alunos.filter(function (aluno) {
      if (turmaId && String(aluno.turma_id) !== String(turmaId)) {
        return false;
      }
      if (!termo) {
        return true;
      }
      const alvo = normalizarBusca([aluno.nome, aluno.email, aluno.login, aluno.celular].join(' '));
      return alvo.indexOf(termo) !== -1;
    });
  }

  function renderLista() {
    const filtrados = alunosFiltrados();
    const total = filtrados.length;
    const paginas = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (state.pagina > paginas) {
      state.pagina = paginas;
    }
    if (state.pagina < 1) {
      state.pagina = 1;
    }

    if (!total) {
      const mensagem = filtrosAtivos()
        ? 'Nenhum aluno encontrado com os filtros informados.'
        : 'Nenhum aluno aprovado ainda.';
      tabela.innerHTML = '<tr><td colspan="5" class="text-muted">' + mensagem + '</td></tr>';
      limparPaginacao();
      return;
    }

    const inicio = (state.pagina - 1) * PAGE_SIZE;
    const paginaAtual = filtrados.slice(inicio, inicio + PAGE_SIZE);

    tabela.innerHTML = paginaAtual.map(function (aluno) {
      const contato = escapeHtml(aluno.email) + (aluno.celular ? '<br><span class="small text-muted">' + escapeHtml(aluno.celular) + '</span>' : '');
      return '<tr><td>' + escapeHtml(aluno.nome) + '</td><td>' + contato + '</td>' +
        '<td>' + escapeHtml(aluno.turma_codigo) + '</td>' +
        '<td>' + statusBadge(aluno.status) + '</td>' +
        '<td class="text-end text-nowrap">' +
        '<button type="button" class="btn btn-sm btn-outline-secondary me-1 js-editar-aluno" data-id="' + aluno.id + '">Editar</button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger js-excluir-aluno" data-id="' + aluno.id + '">Excluir</button>' +
        '</td></tr>';
    }).join('');

    renderPaginacao(total, state.pagina, paginas);
  }

  function limparPaginacao() {
    if (alunosResumo) {
      alunosResumo.textContent = '';
    }
    if (alunosPaginas) {
      alunosPaginas.innerHTML = '';
    }
  }

  function renderPaginacao(total, pagina, paginas) {
    if (!alunosResumo || !alunosPaginas) {
      return;
    }

    const inicio = (pagina - 1) * PAGE_SIZE + 1;
    const fim = Math.min(pagina * PAGE_SIZE, total);
    alunosResumo.textContent = 'Mostrando ' + inicio + '–' + fim + ' de ' + total;

    if (paginas <= 1) {
      alunosPaginas.innerHTML = '';
      return;
    }

    let html = paginaItem('Anterior', pagina - 1, pagina === 1, false);
    let start = 1;
    let end = paginas;
    if (paginas > 7) {
      start = Math.max(1, pagina - 2);
      end = Math.min(paginas, start + 4);
      start = Math.max(1, end - 4);
    }
    if (start > 1) {
      html += paginaItem('1', 1, false, pagina === 1);
      if (start > 2) {
        html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
      }
    }
    for (let i = start; i <= end; i++) {
      html += paginaItem(String(i), i, false, i === pagina);
    }
    if (end < paginas) {
      if (end < paginas - 1) {
        html += '<li class="page-item disabled"><span class="page-link">…</span></li>';
      }
      html += paginaItem(String(paginas), paginas, false, pagina === paginas);
    }
    html += paginaItem('Próxima', pagina + 1, pagina === paginas, false);
    alunosPaginas.innerHTML = html;
  }

  function paginaItem(label, pagina, disabled, active) {
    if (disabled || active) {
      return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">' +
        '<span class="page-link"' + (active ? ' aria-current="page"' : '') + '>' + escapeHtml(label) + '</span></li>';
    }
    return '<li class="page-item"><button type="button" class="page-link js-pagina" data-pagina="' + pagina + '">' +
      escapeHtml(label) + '</button></li>';
  }

  async function reloadAprovados() {
    const response = await apiRequest('/alunos');
    renderAprovados(response.data || []);
  }

  function statusBadge(status) {
    if (status === 'recusado') {
      return '<span class="badge text-bg-danger">Recusado</span>';
    }
    if (status === 'pendente') {
      return '<span class="badge ete-badge-pending">Pendente</span>';
    }
    return '<span class="badge ete-badge-ok">Aprovado</span>';
  }

  function findAluno(id) {
    return state.alunos.filter(function (item) { return String(item.id) === String(id); })[0];
  }

  async function onTableClick(event) {
    if (!event.target.closest) {
      return;
    }

    const editar = event.target.closest('.js-editar-aluno');
    if (editar) {
      abrirEdicao(findAluno(editar.getAttribute('data-id')));
      return;
    }

    const excluir = event.target.closest('.js-excluir-aluno');
    if (excluir) {
      await excluirAluno(excluir, findAluno(excluir.getAttribute('data-id')));
    }
  }

  function abrirEdicao(aluno) {
    if (!aluno) {
      return;
    }

    campos.id.value = aluno.id;
    campos.nome.value = aluno.nome || '';
    campos.email.value = masks ? masks.maskEmail(aluno.email || '') : (aluno.email || '');
    campos.celular.value = masks ? masks.maskCelular(aluno.celular || '') : (aluno.celular || '');
    campos.turma.innerHTML = turmaOptions(state.turmas);
    campos.turma.value = String(aluno.turma_id);
    if (campos.status) {
      campos.status.value = aluno.status || 'aprovado';
    }

    if (modal) {
      modal.show();
    }
  }

  async function onSubmitEdicao(event) {
    event.preventDefault();
    if (masks) {
      campos.email.value = masks.maskEmail(campos.email.value);
      campos.celular.value = masks.maskCelular(campos.celular.value);
    }

    const payload = {
      id: campos.id.value,
      nome: campos.nome.value.trim(),
      email: campos.email.value.trim(),
      celular: campos.celular.value.trim(),
      turma_id: campos.turma.value,
      status: campos.status ? campos.status.value : 'aprovado'
    };

    if (!payload.nome || !payload.email || !payload.turma_id) {
      window.showPortalFeedback('Preencha nome, e-mail e turma.', 'warning');
      return;
    }

    if (masks && !masks.emailValido(payload.email)) {
      window.showPortalFeedback('Informe um e-mail válido, sem espaços. Ex.: nome.sobrenome@escola.com', 'warning');
      return;
    }

    var digitosCelular = payload.celular.replace(/\D/g, '');
    if (digitosCelular && digitosCelular.length !== 11) {
      window.showPortalFeedback('Informe o celular no formato (81)99794-1373 ou deixe em branco.', 'warning');
      return;
    }

    botaoSalvar.disabled = true;
    try {
      const response = await apiRequest('/alunos/update', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      if (modal) {
        modal.hide();
      }
      await reloadListas();
      window.showPortalFeedback(response.message || 'Aluno atualizado.', 'success');
    } catch (error) {
      window.showPortalFeedback(error.message, 'danger');
    } finally {
      botaoSalvar.disabled = false;
    }
  }

  async function reloadListas() {
    const results = await Promise.all([
      apiRequest('/alunos/pendentes'),
      apiRequest('/alunos')
    ]);
    renderPendentes(results[0].data || [], state.turmas);
    renderAprovados(results[1].data || []);
  }

  async function onResetSenha() {
    const id = campos.id.value;
    if (!id) {
      return;
    }

    if (!window.confirm('Redefinir a senha deste aluno para @ETE2626?')) {
      return;
    }

    botaoResetSenha.disabled = true;
    try {
      const response = await apiRequest('/alunos/reset-senha', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
      });
      window.showPortalFeedback(response.message || 'Senha redefinida. O aluno entra com @ETE2626.', 'success');
    } catch (error) {
      window.showPortalFeedback(error.message, 'danger');
    } finally {
      botaoResetSenha.disabled = false;
    }
  }

  async function excluirAluno(button, aluno) {
    if (!aluno) {
      return;
    }

    if (!window.confirm('Excluir o aluno "' + aluno.nome + '"? A conta será removida e ele perderá o acesso ao portal.')) {
      return;
    }

    button.disabled = true;
    try {
      const response = await apiRequest('/alunos/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: aluno.id })
      });
      await reloadAprovados();
      window.showPortalFeedback(response.message || 'Aluno excluído.', 'success');
    } catch (error) {
      button.disabled = false;
      window.showPortalFeedback(error.message, 'danger');
    }
  }
})();
