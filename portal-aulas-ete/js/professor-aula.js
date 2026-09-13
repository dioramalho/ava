(function () {
  if (!window.location.pathname.endsWith('professor-aula.html')) {
    return;
  }

  const form = document.getElementById('aulaForm');
  const videoList = document.getElementById('videoList');
  const addVideoBtn = document.getElementById('addVideoBtn');
  const aulaId = document.getElementById('aulaId');
  const arquivosAtuais = document.getElementById('arquivosAtuais');
  const avisoVinculo = document.getElementById('avisoVinculo');
  const excluirAulaBtn = document.getElementById('excluirAulaBtn');

  initialize();

  async function initialize() {
    const session = await window.validatePortalSession({ requiredProfile: 'professor' });
    if (!session) {
      return;
    }

    const lists = await Promise.all([apiRequest('/turmas'), apiRequest('/disciplinas')]);
    const turmas = lists[0].data || [];
    const disciplinas = lists[1].data || [];

    fillSelect(document.getElementById('aulaTurma'), turmas, 'Selecione a turma');
    fillSelect(document.getElementById('aulaDisciplina'), disciplinas, 'Selecione a disciplina');

    if (!turmas.length || !disciplinas.length) {
      avisoVinculo.classList.remove('d-none');
      form.querySelector('button[type="submit"]').disabled = true;
    }

    addVideoBtn.addEventListener('click', function () {
      addVideoInput('');
    });

    form.addEventListener('submit', onSubmit);
    arquivosAtuais.addEventListener('click', onArquivoClick);
    if (excluirAulaBtn) {
      excluirAulaBtn.addEventListener('click', onDelete);
    }

    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');
    if (id) {
      await loadAula(id);
    }
  }

  function fillSelect(select, items, emptyLabel) {
    select.innerHTML = '<option value="">' + emptyLabel + '</option>' + items.map(function (item) {
      return '<option value="' + item.id + '">' + escapeHtml(item.codigo) + ' — ' + escapeHtml(item.titulo) + '</option>';
    }).join('');
  }

  function addVideoInput(value) {
    const input = document.createElement('input');
    input.className = 'form-control mb-2 js-video';
    input.type = 'url';
    input.placeholder = 'https://www.youtube.com/watch?v=...';
    input.value = value || '';
    videoList.appendChild(input);
  }

  function renderArquivos(arquivos) {
    if (!arquivos || !arquivos.length) {
      arquivosAtuais.innerHTML = '';
      return;
    }

    arquivosAtuais.innerHTML = '<p class="small text-muted mb-2">Arquivos já publicados:</p><ul class="list-unstyled small mb-3">' +
      arquivos.map(function (arquivo) {
        return '<li class="d-flex align-items-center justify-content-between gap-2 mb-2">' +
          '<a href="' + escapeHtml(arquivo.caminho_arquivo) + '" target="_blank" rel="noopener">' + escapeHtml(arquivo.nome_original) + '</a>' +
          '<button type="button" class="btn btn-sm btn-outline-danger js-excluir-arquivo" data-id="' + arquivo.id + '" data-nome="' + escapeHtml(arquivo.nome_original) + '">Excluir</button>' +
          '</li>';
      }).join('') + '</ul>';
  }

  async function loadAula(id) {
    const response = await apiRequest('/aulas/detalhe?id=' + encodeURIComponent(id));
    const aula = response.data;
    aulaId.value = aula.id;
    document.getElementById('aulaTitulo').value = aula.titulo;
    document.getElementById('aulaTurma').value = aula.turma_id;
    document.getElementById('aulaDisciplina').value = aula.disciplina_id;
    document.querySelector('h1').textContent = 'Editar aula';
    excluirAulaBtn.classList.remove('d-none');

    videoList.innerHTML = '';
    const videos = aula.videos || [];
    if (!videos.length) {
      addVideoInput('');
    } else {
      videos.forEach(function (video) {
        addVideoInput(video.url);
      });
    }

    renderArquivos(aula.arquivos || []);
  }

  async function onArquivoClick(event) {
    const button = event.target.closest ? event.target.closest('.js-excluir-arquivo') : null;
    if (!button) {
      return;
    }

    const arquivoId = button.getAttribute('data-id');
    const nome = button.getAttribute('data-nome') || 'este arquivo';
    if (!aulaId.value || !arquivoId) {
      return;
    }
    if (!window.confirm('Excluir o arquivo "' + nome + '"? Esta ação não pode ser desfeita.')) {
      return;
    }

    button.disabled = true;
    try {
      await apiRequest('/aulas/arquivos/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          aula_id: aulaId.value,
          id: arquivoId
        })
      });
      const detalhe = await apiRequest('/aulas/detalhe?id=' + encodeURIComponent(aulaId.value));
      renderArquivos((detalhe.data && detalhe.data.arquivos) || []);
    } catch (error) {
      button.disabled = false;
      window.alert(error.message);
    }
  }

  async function onSubmit(event) {
    event.preventDefault();
    const videos = Array.prototype.map.call(document.querySelectorAll('.js-video'), function (input) {
      return input.value.trim();
    }).filter(Boolean);

    const body = new FormData();
    if (aulaId.value) {
      body.append('id', aulaId.value);
    }
    body.append('titulo', document.getElementById('aulaTitulo').value.trim());
    body.append('turma_id', document.getElementById('aulaTurma').value);
    body.append('disciplina_id', document.getElementById('aulaDisciplina').value);
    body.append('videos', JSON.stringify(videos));

    const files = document.getElementById('aulaArquivos').files;
    for (let i = 0; i < files.length; i++) {
      body.append('arquivos[]', files[i]);
    }

    const route = aulaId.value ? '/aulas/update' : '/aulas';
    try {
      await apiRequest(route, { method: 'POST', body: body });
      window.location.href = 'professor-aulas.html';
    } catch (error) {
      window.alert(error.message);
    }
  }

  async function onDelete() {
    const id = aulaId.value;
    const titulo = document.getElementById('aulaTitulo').value.trim() || 'esta aula';
    if (!id) {
      return;
    }
    if (!window.confirm('Excluir a aula "' + titulo + '"? Os arquivos enviados também serão removidos.')) {
      return;
    }

    excluirAulaBtn.disabled = true;
    try {
      await apiRequest('/aulas/delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: id })
      });
      window.location.href = 'professor-aulas.html';
    } catch (error) {
      excluirAulaBtn.disabled = false;
      window.alert(error.message);
    }
  }
})();
