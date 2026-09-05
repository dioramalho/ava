(function () {
  if (!window.location.pathname.endsWith('dashboard-professor.html')) {
    return;
  }

  const countTurmas = document.getElementById('countTurmas');
  const countDisciplinas = document.getElementById('countDisciplinas');
  const countAulas = document.getElementById('countAulas');
  const countAlunos = document.getElementById('countAlunos');
  const pendentesWrap = document.getElementById('pendentesWrap');
  const aulasRecentes = document.getElementById('aulasRecentes');
  const badgePendentes = document.getElementById('badgePendentes');

  initialize();

  async function initialize() {
    try {
      const session = await window.validatePortalSession({ requiredProfile: 'professor' });
      if (!session) {
        return;
      }

      const response = await apiRequest('/professor/painel');
      const data = response.data || {};
      render(data);
    } catch (error) {
      if (pendentesWrap) {
        pendentesWrap.innerHTML = '<p class="text-danger mb-0">' + (error.message || 'Erro ao carregar o painel.') + '</p>';
      }
    }
  }

  function render(data) {
    countTurmas.textContent = data.turmas || 0;
    countDisciplinas.textContent = data.disciplinas || 0;
    countAulas.textContent = data.aulas || 0;
    countAlunos.textContent = data.alunos_ativos || 0;

    const pendentes = data.pendentes || [];
    const turmas = data.lista_turmas || [];
    if (badgePendentes) {
      badgePendentes.textContent = (data.solicitacoes_pendentes || 0) + ' pendentes';
    }

    if (!pendentes.length) {
      pendentesWrap.innerHTML = '<p class="text-muted mb-0">Nenhuma solicitação pendente.</p>';
    } else {
      pendentesWrap.innerHTML = pendentes.map(function (aluno) {
        return requestCard(aluno, turmas);
      }).join('');
      bindAprovacoes(pendentesWrap);
    }

    const recentes = data.aulas_recentes || [];
    if (!recentes.length) {
      aulasRecentes.innerHTML = '<li class="list-group-item px-0 text-muted">Nenhuma aula publicada ainda.</li>';
      return;
    }

    aulasRecentes.innerHTML = recentes.map(function (aula) {
      return '<li class="list-group-item px-0 d-flex justify-content-between gap-3">' +
        '<div><strong>' + escapeHtml(aula.titulo) + '</strong>' +
        '<p class="small text-muted mb-0">' + escapeHtml(aula.turma_codigo) + ' · ' + escapeHtml(aula.disciplina_titulo) + '</p></div>' +
        '<span class="badge ete-badge-ok align-self-center">Visível</span></li>';
    }).join('');
  }

  function requestCard(aluno, turmas) {
    const options = turmas.map(function (turma) {
      return '<option value="' + turma.id + '">' + escapeHtml(turma.codigo) + ' — ' + escapeHtml(turma.titulo) + '</option>';
    }).join('');

    return '<div class="ete-card ete-request-card p-3" data-aluno-id="' + aluno.id + '">' +
      '<div class="d-flex flex-column flex-md-row justify-content-between gap-3">' +
      '<div><strong>' + escapeHtml(aluno.nome) + '</strong>' +
      '<p class="mb-1 small text-muted">' + escapeHtml(aluno.email) + ' · ' + escapeHtml(aluno.celular || '') + '</p>' +
      '<label class="form-label small mb-1">Vincular à turma</label>' +
      '<select class="form-select form-select-sm js-turma" style="max-width: 240px">' + options + '</select></div>' +
      '<div class="d-flex gap-2 align-items-end">' +
      '<button type="button" class="btn btn-ete-green btn-sm btn-touch js-aprovar">Aprovar</button>' +
      '<button type="button" class="btn btn-outline-danger btn-sm btn-touch js-recusar">Recusar</button>' +
      '</div></div></div>';
  }

  function bindAprovacoes(root) {
    root.querySelectorAll('[data-aluno-id]').forEach(function (card) {
      const id = card.getAttribute('data-aluno-id');
      card.querySelector('.js-aprovar').addEventListener('click', async function () {
        const turmaId = card.querySelector('.js-turma').value;
        try {
          await apiRequest('/alunos/aprovar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, turma_id: turmaId })
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
})();
