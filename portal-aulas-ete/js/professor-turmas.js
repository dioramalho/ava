(function () {
  if (!window.location.pathname.endsWith('professor-turmas.html')) {
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
    await load();
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
        '<td class="text-end"><button class="btn btn-sm btn-outline-secondary" type="button" data-edit="' + turma.id + '">Editar</button></td>' +
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
      campoId.value = '';
      form.reset();
      botao.textContent = 'Cadastrar turma';
      await load();
    } catch (error) {
      window.alert(error.message);
    }
  }
})();
