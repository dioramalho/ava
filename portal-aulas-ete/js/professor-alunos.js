(function () {
  const isAlunoPage = window.location.pathname.endsWith('professor-alunos.html');
  if (!isAlunoPage) {
    return;
  }

  const alunoForm = document.getElementById('alunoForm');
  const alunosContainer = document.getElementById('alunosContainer');
  const formTitle = document.getElementById('formTitle');
  const saveAlunoBtn = document.getElementById('saveAlunoBtn');
  const cancelEditBtn = document.getElementById('cancelEditBtn');

  const fields = {
    id: document.getElementById('alunoId'),
    turma: document.getElementById('alunoTurma'),
    nome: document.getElementById('alunoNome'),
    email: document.getElementById('alunoEmail'),
    matricula: document.getElementById('alunoMatricula')
  };

  const state = {
    turmas: [],
    alunos: []
  };

  if (!alunoForm || !alunosContainer) {
    return;
  }

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    bindEvents();
    await refreshData();
  }

  function bindEvents() {
    alunoForm.addEventListener('submit', handleFormSubmit);
    cancelEditBtn.addEventListener('click', resetForm);
    alunosContainer.addEventListener('click', handleTableActions);
  }

  async function refreshData() {
    try {
      const responses = await Promise.all([
        apiRequest('/turmas'),
        apiRequest('/alunos')
      ]);

      state.turmas = responses[0].data || [];
      state.alunos = responses[1].data || [];

      renderTurmas();
      renderAlunosTable();
    } catch (error) {
      alunosContainer.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message || 'Não foi possível carregar os alunos.') + '</div>';
    }
  }

  async function handleFormSubmit(event) {
    event.preventDefault();

    const payload = {
      id: fields.id.value.trim(),
      turma_id: fields.turma.value,
      nome: fields.nome.value.trim(),
      email: fields.email.value.trim(),
      matricula: fields.matricula.value.trim()
    };

    if (!payload.turma_id || !payload.nome || !payload.email || !payload.matricula) {
      showToast('Preencha turma, nome, e-mail e matrícula.', 'warning');
      return;
    }

    const isEditing = payload.id !== '';
    const route = isEditing ? '/alunos/update' : '/alunos';

    try {
      await apiRequest(route, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      showToast(isEditing ? 'Aluno atualizado com sucesso.' : 'Aluno cadastrado com sucesso.', 'success');
      resetForm();
      await refreshData();
    } catch (error) {
      showToast(error.message || 'Não foi possível salvar o aluno.', 'danger');
    }
  }

  function handleTableActions(event) {
    const editButton = event.target.closest('[data-action="edit"]');
    if (editButton) {
      const alunoId = editButton.getAttribute('data-id');
      startEdit(alunoId);
      return;
    }

    const deleteButton = event.target.closest('[data-action="delete"]');
    if (deleteButton) {
      const alunoId = deleteButton.getAttribute('data-id');
      deleteAluno(alunoId);
    }
  }

  function startEdit(alunoId) {
    const aluno = state.alunos.find(function (item) {
      return String(item.id) === String(alunoId);
    });

    if (!aluno) {
      showToast('Aluno não encontrado para edição.', 'warning');
      return;
    }

    fields.id.value = aluno.id;
    fields.turma.value = aluno.turma_id;
    fields.nome.value = aluno.nome;
    fields.email.value = aluno.email;
    fields.matricula.value = aluno.matricula;

    formTitle.textContent = 'Editar aluno';
    saveAlunoBtn.textContent = 'Salvar alterações';
    cancelEditBtn.classList.remove('d-none');
    fields.nome.focus();
  }

  async function deleteAluno(alunoId) {
    const confirmed = window.confirm('Tem certeza que deseja excluir o aluno?');
    if (!confirmed) {
      return;
    }

    try {
      await apiRequest('/alunos/delete', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: alunoId })
      });

      showToast('Aluno excluído com sucesso.', 'success');
      if (String(fields.id.value) === String(alunoId)) {
        resetForm();
      }
      await refreshData();
    } catch (error) {
      showToast(error.message || 'Não foi possível excluir o aluno.', 'danger');
    }
  }

  function renderTurmas() {
    const previousValue = fields.turma.value;
    const orderedTurmas = state.turmas.slice().sort(function (a, b) {
      return String(a.nome).localeCompare(String(b.nome));
    });

    fields.turma.innerHTML = '';

    if (!orderedTurmas.length) {
      fields.turma.innerHTML = '<option value="">Cadastre uma turma primeiro</option>';
      return;
    }

    orderedTurmas.forEach(function (turma) {
      const option = document.createElement('option');
      option.value = turma.id;
      option.textContent = turma.nome + ' • ' + turma.ano_letivo + ' • ' + turma.turno;
      fields.turma.appendChild(option);
    });

    if (previousValue && orderedTurmas.some(function (turma) { return String(turma.id) === String(previousValue); })) {
      fields.turma.value = previousValue;
    }
  }

  function renderAlunosTable() {
    if (!state.alunos.length) {
      alunosContainer.innerHTML = '<p class="text-muted mb-0">Nenhum aluno cadastrado.</p>';
      return;
    }

    const rows = state.alunos.map(function (aluno) {
      return [
        '<tr>',
          '<td>' + escapeHtml(aluno.nome) + '</td>',
          '<td>' + escapeHtml(aluno.email) + '</td>',
          '<td>' + escapeHtml(aluno.matricula) + '</td>',
          '<td>' + escapeHtml(aluno.turma_nome) + '</td>',
          '<td class="text-end">',
            '<div class="btn-group btn-group-sm" role="group" aria-label="Ações do aluno">',
              '<button type="button" class="btn btn-outline-primary" data-action="edit" data-id="' + escapeAttribute(aluno.id) + '"><i class="bi bi-pencil-square"></i></button>',
              '<button type="button" class="btn btn-outline-danger" data-action="delete" data-id="' + escapeAttribute(aluno.id) + '"><i class="bi bi-trash"></i></button>',
            '</div>',
          '</td>',
        '</tr>'
      ].join('');
    }).join('');

    alunosContainer.innerHTML = [
      '<table class="table table-hover align-middle mb-0">',
        '<thead>',
          '<tr>',
            '<th>Nome</th>',
            '<th>E-mail</th>',
            '<th>Matrícula</th>',
            '<th>Turma</th>',
            '<th class="text-end">Ações</th>',
          '</tr>',
        '</thead>',
        '<tbody>' + rows + '</tbody>',
      '</table>'
    ].join('');
  }

  function resetForm() {
    alunoForm.reset();
    fields.id.value = '';
    formTitle.textContent = 'Novo aluno';
    saveAlunoBtn.textContent = 'Cadastrar aluno';
    cancelEditBtn.classList.add('d-none');

    if (!fields.turma.options.length && state.turmas.length) {
      renderTurmas();
    }
  }

  function showToast(message, variant) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + variant + ' position-fixed top-0 end-0 m-3 shadow';
    alert.style.zIndex = '1080';
    alert.textContent = message;
    document.body.appendChild(alert);

    setTimeout(function () {
      alert.remove();
    }, 2500);
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#96;');
  }
})();
