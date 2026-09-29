(function () {
  if (!window.isCurrentPortalFile('professor-alunos.php')) {
    return;
  }

  const pendentesWrap = document.getElementById('pendentesWrap');
  const tabela = document.getElementById('alunosTableBody');
  const badgePendentes = document.getElementById('badgePendentes');
  const modalEl = document.getElementById('alunoModal');
  const form = document.getElementById('alunoForm');
  const botaoSalvar = document.getElementById('alunoSubmit');
  const campos = {
    id: document.getElementById('alunoId'),
    nome: document.getElementById('alunoNome'),
    email: document.getElementById('alunoEmail'),
    celular: document.getElementById('alunoCelular'),
    turma: document.getElementById('alunoTurma')
  };

  const state = {
    turmas: [],
    alunos: []
  };

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

    try {
      const results = await Promise.all([
        apiRequest('/alunos/pendentes'),
        apiRequest('/alunos'),
        apiRequest('/turmas')
      ]);
      state.turmas = results[2].data || [];
      renderPendentes(results[0].data || [], state.turmas);
      renderAprovados(results[1].data || []);
    } catch (error) {
      pendentesWrap.innerHTML = '<p class="text-danger mb-0">' + escapeHtml(error.message) + '</p>';
      tabela.innerHTML = '<tr><td colspan="5" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
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

  function renderAprovados(alunos) {
    state.alunos = alunos;
    if (!alunos.length) {
      tabela.innerHTML = '<tr><td colspan="5" class="text-muted">Nenhum aluno aprovado ainda.</td></tr>';
      return;
    }

    tabela.innerHTML = alunos.map(function (aluno) {
      const contato = escapeHtml(aluno.email) + (aluno.celular ? '<br><span class="small text-muted">' + escapeHtml(aluno.celular) + '</span>' : '');
      return '<tr><td>' + escapeHtml(aluno.nome) + '</td><td>' + contato + '</td>' +
        '<td>' + escapeHtml(aluno.turma_codigo) + '</td>' +
        '<td><span class="badge ete-badge-ok">Aprovado</span></td>' +
        '<td class="text-end text-nowrap">' +
        '<button type="button" class="btn btn-sm btn-outline-secondary me-1 js-editar-aluno" data-id="' + aluno.id + '">Editar</button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger js-excluir-aluno" data-id="' + aluno.id + '">Excluir</button>' +
        '</td></tr>';
    }).join('');
  }

  async function reloadAprovados() {
    const response = await apiRequest('/alunos');
    renderAprovados(response.data || []);
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
    campos.email.value = aluno.email || '';
    campos.celular.value = aluno.celular || '';
    campos.turma.innerHTML = turmaOptions(state.turmas);
    campos.turma.value = String(aluno.turma_id);

    if (modal) {
      modal.show();
    }
  }

  async function onSubmitEdicao(event) {
    event.preventDefault();
    const payload = {
      id: campos.id.value,
      nome: campos.nome.value.trim(),
      email: campos.email.value.trim(),
      celular: campos.celular.value.trim(),
      turma_id: campos.turma.value
    };

    if (!payload.nome || !payload.email || !payload.turma_id) {
      window.showPortalFeedback('Preencha nome, e-mail e turma.', 'warning');
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
      await reloadAprovados();
      window.showPortalFeedback(response.message || 'Aluno atualizado.', 'success');
    } catch (error) {
      window.showPortalFeedback(error.message, 'danger');
    } finally {
      botaoSalvar.disabled = false;
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
