(function () {
  if (!window.isCurrentPortalFile('professor-disciplinas.php')) {
    return;
  }

  const form = document.getElementById('disciplinaForm');
  const lista = document.getElementById('disciplinasLista');
  const campoId = document.getElementById('discId');
  const campoCodigo = document.getElementById('discCodigo');
  const campoTitulo = document.getElementById('discTitulo');
  const botao = document.getElementById('discSubmit');
  const botaoCancelar = document.getElementById('discCancelar');
  const tituloForm = document.getElementById('disciplinaFormTitulo');

  let itens = [];

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    form.addEventListener('submit', onSubmit);
    botaoCancelar.addEventListener('click', resetForm);
    lista.addEventListener('click', onListClick);
    try {
      await load();
    } catch (error) {
      lista.innerHTML = '<p class="text-danger mb-0">' + escapeHtml(error.message) + '</p>';
    }
  }

  async function load() {
    const response = await apiRequest('/disciplinas');
    itens = response.data || [];
    if (!itens.length) {
      lista.innerHTML = '<p class="text-muted mb-0">Nenhuma disciplina cadastrada.</p>';
      return;
    }

    lista.innerHTML = '<div class="row g-3">' + itens.map(function (item) {
      return '<div class="col-md-6"><div class="ete-card p-3 h-100 d-flex flex-column" style="border-top: 4px solid var(--ete-navy)">' +
        '<span class="small text-muted">' + escapeHtml(item.codigo) + '</span>' +
        '<h3 class="h6 fw-bold mb-3">' + escapeHtml(item.titulo) + '</h3>' +
        '<div class="mt-auto d-flex gap-2 justify-content-end">' +
        '<button type="button" class="btn btn-sm btn-outline-secondary" data-edit="' + item.id + '">Editar</button>' +
        '<button type="button" class="btn btn-sm btn-outline-danger" data-delete="' + item.id + '">Excluir</button>' +
        '</div></div></div>';
    }).join('') + '</div>';
  }

  function findItem(id) {
    return itens.filter(function (item) { return String(item.id) === String(id); })[0];
  }

  function onListClick(event) {
    if (!event.target.closest) {
      return;
    }

    const editar = event.target.closest('[data-edit]');
    if (editar) {
      const item = findItem(editar.getAttribute('data-edit'));
      if (item) {
        campoId.value = item.id;
        campoCodigo.value = item.codigo;
        campoTitulo.value = item.titulo;
        botao.textContent = 'Salvar alterações';
        tituloForm.textContent = 'Editar disciplina';
        botaoCancelar.classList.remove('d-none');
        campoCodigo.focus();
      }
      return;
    }

    const excluir = event.target.closest('[data-delete]');
    if (excluir) {
      const item = findItem(excluir.getAttribute('data-delete'));
      if (item) {
        excluirDisciplina(excluir, item);
      }
    }
  }

  function resetForm() {
    campoId.value = '';
    form.reset();
    botao.textContent = 'Cadastrar disciplina';
    tituloForm.textContent = 'Nova disciplina';
    botaoCancelar.classList.add('d-none');
  }

  async function excluirDisciplina(button, item) {
    if (!window.confirm('Excluir a disciplina "' + item.codigo + ' — ' + item.titulo + '"? Esta ação não pode ser desfeita.')) {
      return;
    }

    button.disabled = true;
    try {
      const response = await apiRequest('/disciplinas/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: item.id })
      });
      if (String(campoId.value) === String(item.id)) {
        resetForm();
      }
      await load();
      window.showPortalFeedback(response.message || 'Disciplina excluída.', 'success');
    } catch (error) {
      button.disabled = false;
      window.showPortalFeedback(error.message, 'danger');
    }
  }

  async function onSubmit(event) {
    event.preventDefault();
    const payload = {
      codigo: campoCodigo.value.trim(),
      titulo: campoTitulo.value.trim()
    };
    const id = campoId.value;
    try {
      let response;
      if (id) {
        payload.id = id;
        response = await apiRequest('/disciplinas/update', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
      } else {
        response = await apiRequest('/disciplinas', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
      }
      resetForm();
      await load();
      if (id) {
        window.showPortalFeedback(response.message || 'Disciplina atualizada.', 'success');
      }
    } catch (error) {
      window.alert(error.message);
    }
  }
})();
