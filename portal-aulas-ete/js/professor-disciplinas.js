(function () {
  if (!window.location.pathname.endsWith('professor-disciplinas.html')) {
    return;
  }

  const form = document.getElementById('disciplinaForm');
  const lista = document.getElementById('disciplinasLista');

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
    const response = await apiRequest('/disciplinas');
    const itens = response.data || [];
    if (!itens.length) {
      lista.innerHTML = '<p class="text-muted mb-0">Nenhuma disciplina cadastrada.</p>';
      return;
    }

    lista.innerHTML = '<div class="row g-3">' + itens.map(function (item) {
      return '<div class="col-md-6"><div class="ete-card p-3 h-100" style="border-top: 4px solid var(--ete-navy)">' +
        '<span class="small text-muted">' + escapeHtml(item.codigo) + '</span>' +
        '<h3 class="h6 fw-bold mb-0">' + escapeHtml(item.titulo) + '</h3></div></div>';
    }).join('') + '</div>';
  }

  async function onSubmit(event) {
    event.preventDefault();
    try {
      await apiRequest('/disciplinas', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          codigo: document.getElementById('discCodigo').value.trim(),
          titulo: document.getElementById('discTitulo').value.trim()
        })
      });
      form.reset();
      await load();
    } catch (error) {
      window.alert(error.message);
    }
  }
})();
