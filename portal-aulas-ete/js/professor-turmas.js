(function () {
  if (!window.isCurrentPortalFile('professor-turmas.php')) {
    return;
  }

  const form = document.getElementById('turmaForm');
  const tabela = document.getElementById('turmasTableBody');
  const campoId = document.getElementById('turmaId');
  const botao = document.getElementById('turmaSubmit');

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    form.addEventListener('submit', onSubmit);
    try {
      await load();
    } catch (error) {
      tabela.innerHTML = '<tr><td colspan="4" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
    }
  }

  async function load() {
    const response = await apiRequest('/turmas');
    const turmas = response.data || [];
    if (!turmas.length) {
      tabela.innerHTML = '<tr><td colspan="4" class="text-muted">Nenhuma turma cadastrada.</td></tr>';
      return;
    }

    tabela.innerHTML = turmas.map(function (turma) {
      return '<tr>' +
        '<td><strong>' + escapeHtml(turma.codigo) + '</strong></td>' +
        '<td>' + escapeHtml(turma.titulo) + '</td>' +
        '<td>' + escapeHtml(turma.alunos_count) + '</td>' +
        '<td class="text-end text-nowrap">' +
        '<button class="btn btn-sm btn-outline-secondary me-1" type="button" data-edit="' + turma.id + '">Editar</button>' +
        '<button class="btn btn-sm btn-outline-danger" type="button" data-delete="' + turma.id + '">Excluir</button>' +
        '</td>' +
        '</tr>';
    }).join('');

    tabela.querySelectorAll('[data-edit]').forEach(function (button) {
      button.addEventListener('click', function () {
        const id = button.getAttribute('data-edit');
        const turma = turmas.filter(function (item) { return String(item.id) === String(id); })[0];
        if (!turma) {
          return;
        }
        campoId.value = turma.id;
        document.getElementById('turmaCodigo').value = turma.codigo;
        document.getElementById('turmaTitulo').value = turma.titulo;
        botao.textContent = 'Salvar alterações';
      });
    });

    tabela.querySelectorAll('[data-delete]').forEach(function (button) {
      button.addEventListener('click', function () {
        const id = button.getAttribute('data-delete');
        const turma = turmas.filter(function (item) { return String(item.id) === String(id); })[0];
        if (turma) {
          excluir(button, turma);
        }
      });
    });
  }

  function resetForm() {
    campoId.value = '';
    form.reset();
    botao.textContent = 'Cadastrar turma';
  }

  async function excluir(button, turma) {
    if (!window.confirm('Excluir a turma "' + turma.codigo + '"? Esta ação não pode ser desfeita.')) {
      return;
    }

    button.disabled = true;
    try {
      const response = await apiRequest('/turmas/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: turma.id })
      });
      if (String(campoId.value) === String(turma.id)) {
        resetForm();
      }
      await load();
      window.showPortalFeedback(response.message || 'Turma excluída.', 'success');
    } catch (error) {
      button.disabled = false;
      window.showPortalFeedback(error.message, 'danger');
    }
  }

  async function onSubmit(event) {
    event.preventDefault();
    const payload = {
      codigo: document.getElementById('turmaCodigo').value.trim(),
      titulo: document.getElementById('turmaTitulo').value.trim()
    };
    const id = campoId.value;
    try {
      if (id) {
        payload.id = id;
        await apiRequest('/turmas/update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
      } else {
        await apiRequest('/turmas', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
      }
      resetForm();
      await load();
    } catch (error) {
      window.alert(error.message);
    }
  }
})();
