(function () {
  if (!window.isCurrentPortalFile('professor-turmas.php')) {
    return;
  }

  const form = document.getElementById('turmaForm');
  const tabela = document.getElementById('turmasTableBody');
  const campoId = document.getElementById('turmaId');
  const botao = document.getElementById('turmaSubmit');
  const botaoArquivadas = document.getElementById('turmasArquivadasBtn');
  const tituloLista = document.getElementById('turmasListaTitulo');
  let mostrandoArquivadas = false;

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    form.addEventListener('submit', onSubmit);
    botaoArquivadas.addEventListener('click', function () {
      mostrandoArquivadas = !mostrandoArquivadas;
      botaoArquivadas.textContent = mostrandoArquivadas ? 'Turmas ativas' : 'Turmas arquivadas';
      tituloLista.textContent = mostrandoArquivadas ? 'Turmas arquivadas' : 'Turmas cadastradas';
      load().catch(function (error) {
        tabela.innerHTML = '<tr><td colspan="4" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
      });
    });
    try {
      await load();
    } catch (error) {
      tabela.innerHTML = '<tr><td colspan="4" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
    }
  }

  async function load() {
    const response = await apiRequest(mostrandoArquivadas ? '/turmas?arquivadas=1' : '/turmas');
    const turmas = response.data || [];
    if (!turmas.length) {
      const vazio = mostrandoArquivadas ? 'Nenhuma turma arquivada.' : 'Nenhuma turma cadastrada.';
      tabela.innerHTML = '<tr><td colspan="4" class="text-muted">' + vazio + '</td></tr>';
      return;
    }

    tabela.innerHTML = turmas.map(function (turma) {
      const acoes = mostrandoArquivadas
        ? '<button class="btn btn-sm btn-outline-secondary me-1" type="button" data-unarchive="' + turma.id + '">Desarquivar</button>' +
          '<button class="btn btn-sm btn-outline-danger" type="button" data-delete="' + turma.id + '">Excluir</button>'
        : '<button class="btn btn-sm btn-outline-secondary me-1" type="button" data-edit="' + turma.id + '">Editar</button>' +
          '<button class="btn btn-sm btn-outline-danger" type="button" data-delete="' + turma.id + '">Excluir</button>';

      return '<tr>' +
        '<td><strong>' + escapeHtml(turma.codigo) + '</strong></td>' +
        '<td>' + escapeHtml(turma.titulo) + '</td>' +
        '<td>' + escapeHtml(turma.alunos_count) + '</td>' +
        '<td class="text-end text-nowrap">' + acoes + '</td>' +
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

    tabela.querySelectorAll('[data-unarchive]').forEach(function (button) {
      button.addEventListener('click', function () {
        const id = button.getAttribute('data-unarchive');
        const turma = turmas.filter(function (item) { return String(item.id) === String(id); })[0];
        if (turma) {
          desarquivar(button, turma);
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
      const podeArquivar = !mostrandoArquivadas && error.status === 409;
      if (podeArquivar && window.confirm((error.message || 'Não é possível excluir a turma.') + '\n\nDeseja arquivá-la? Ela deixará de aparecer na lista ativa e só voltará em Turmas arquivadas.')) {
        try {
          const arquivada = await apiRequest('/turmas/arquivar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: turma.id })
          });
          if (String(campoId.value) === String(turma.id)) {
            resetForm();
          }
          await load();
          window.showPortalFeedback(arquivada.message || 'Turma arquivada.', 'success');
          return;
        } catch (archiveError) {
          button.disabled = false;
          window.showPortalFeedback(archiveError.message, 'danger');
          return;
        }
      }
      button.disabled = false;
      if (!podeArquivar || error.status !== 409) {
        window.showPortalFeedback(error.message, 'danger');
      }
    }
  }

  async function desarquivar(button, turma) {
    if (!window.confirm('Desarquivar a turma "' + turma.codigo + '"? Ela volta para a lista ativa.')) {
      return;
    }

    button.disabled = true;
    try {
      const response = await apiRequest('/turmas/desarquivar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: turma.id })
      });
      await load();
      window.showPortalFeedback(response.message || 'Turma reativada.', 'success');
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
