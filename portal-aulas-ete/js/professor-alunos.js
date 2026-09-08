(function () {
  if (!window.location.pathname.endsWith('professor-alunos.html')) {
    return;
  }

  const pendentesWrap = document.getElementById('pendentesWrap');
  const tabela = document.getElementById('alunosTableBody');
  const badgePendentes = document.getElementById('badgePendentes');

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    try {
      const results = await Promise.all([
        apiRequest('/alunos/pendentes'),
        apiRequest('/alunos'),
        apiRequest('/turmas')
      ]);
      renderPendentes(results[0].data || [], results[2].data || []);
      renderAprovados(results[1].data || []);
    } catch (error) {
      pendentesWrap.innerHTML = '<p class="text-danger mb-0">' + escapeHtml(error.message) + '</p>';
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

    const options = turmas.map(function (turma) {
      return '<option value="' + turma.id + '">' + escapeHtml(turma.codigo) + ' — ' + escapeHtml(turma.titulo) + '</option>';
    }).join('');

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

  function renderAprovados(alunos) {
    if (!alunos.length) {
      tabela.innerHTML = '<tr><td colspan="4" class="text-muted">Nenhum aluno aprovado ainda.</td></tr>';
      return;
    }

    tabela.innerHTML = alunos.map(function (aluno) {
      return '<tr><td>' + escapeHtml(aluno.nome) + '</td><td>' + escapeHtml(aluno.email) + '</td>' +
        '<td>' + escapeHtml(aluno.turma_codigo) + '</td>' +
        '<td><span class="badge ete-badge-ok">Aprovado</span></td></tr>';
    }).join('');
  }
})();
