(function () {
  const isProfessorPage = window.location.pathname.endsWith('dashboard-professor.html');
  if (!isProfessorPage) {
    return;
  }

  const turmaForm = document.getElementById('turmaForm');
  const materiaForm = document.getElementById('materiaForm');
  const pdfForm = document.getElementById('pdfForm');
  const recadoForm = document.getElementById('recadoForm');

  const selectsTurma = {
    materiaTurma: document.getElementById('materiaTurma'),
    pdfTurma: document.getElementById('pdfTurma'),
    recadoTurma: document.getElementById('recadoTurma')
  };

  const fields = {
    turmaNome: document.getElementById('turmaNome'),
    turmaAnoLetivo: document.getElementById('turmaAnoLetivo'),
    turmaTurno: document.getElementById('turmaTurno'),
    turmaDescricao: document.getElementById('turmaDescricao'),
    materiaNome: document.getElementById('materiaNome'),
    materiaDescricao: document.getElementById('materiaDescricao'),
    pdfMateria: document.getElementById('pdfMateria'),
    pdfTitulo: document.getElementById('pdfTitulo'),
    pdfDescricao: document.getElementById('pdfDescricao'),
    pdfArquivo: document.getElementById('pdfArquivo'),
    recadoTitulo: document.getElementById('recadoTitulo'),
    recadoTexto: document.getElementById('recadoTexto')
  };

  const turmasContainer = document.getElementById('turmasContainer');
  const materiasContainer = document.getElementById('materiasContainer');
  const publicacoesContainer = document.getElementById('publicacoesContainer');

  const state = {
    turmas: [],
    alunos: [],
    materias: [],
    documentos: [],
    recados: []
  };

  if (!turmaForm || !materiaForm || !pdfForm || !recadoForm) {
    return;
  }

  initialize();

  async function initialize() {
    try {
      const session = await window.validatePortalSession({ requiredProfile: 'professor' });
      if (!session) {
        return;
      }

      bindEvents();
      await refreshData();
    } catch (error) {
      renderGlobalError(error.message || 'Não foi possível carregar o dashboard do professor.');
    }
  }

  function bindEvents() {
    turmaForm.addEventListener('submit', handleTurmaSubmit);
    materiaForm.addEventListener('submit', handleMateriaSubmit);
    pdfForm.addEventListener('submit', handlePdfSubmit);
    recadoForm.addEventListener('submit', handleRecadoSubmit);
    selectsTurma.pdfTurma.addEventListener('change', updateMateriaByTurma);
  }

  async function refreshData() {
    const responses = await Promise.all([
      apiRequest('/turmas'),
      apiRequest('/alunos'),
      apiRequest('/materias'),
      apiRequest('/documentos'),
      apiRequest('/recados')
    ]);

    state.turmas = responses[0].data || [];
    state.alunos = responses[1].data || [];
    state.materias = responses[2].data || [];
    state.documentos = responses[3].data || [];
    state.recados = responses[4].data || [];

    renderAll();
  }

  async function handleTurmaSubmit(event) {
    event.preventDefault();

    const payload = {
      nome: fields.turmaNome.value.trim(),
      ano_letivo: fields.turmaAnoLetivo.value.trim(),
      turno: fields.turmaTurno.value,
      descricao: fields.turmaDescricao.value.trim()
    };

    if (!payload.nome || !payload.ano_letivo || !payload.turno) {
      showToast('Preencha nome, ano letivo e turno da turma.', 'warning');
      return;
    }

    await submitJson('/turmas', payload, turmaForm, 'Turma cadastrada com sucesso.');
  }

  async function handleMateriaSubmit(event) {
    event.preventDefault();

    const payload = {
      turma_id: selectsTurma.materiaTurma.value,
      nome: fields.materiaNome.value.trim(),
      descricao: fields.materiaDescricao.value.trim()
    };

    if (!payload.turma_id || !payload.nome) {
      showToast('Informe a turma e o nome da matéria.', 'warning');
      return;
    }

    await submitJson('/materias', payload, materiaForm, 'Matéria cadastrada com sucesso.');
  }

  async function handlePdfSubmit(event) {
    event.preventDefault();

    const turmaId = selectsTurma.pdfTurma.value;
    const materiaId = fields.pdfMateria.value;
    const titulo = fields.pdfTitulo.value.trim();
    const descricao = fields.pdfDescricao.value.trim();
    const arquivo = fields.pdfArquivo.files[0];

    if (!turmaId || !materiaId || !titulo || !arquivo) {
      showToast('Informe turma, matéria, título e um arquivo PDF.', 'warning');
      return;
    }

    const isPdf = arquivo.type === 'application/pdf' || arquivo.name.toLowerCase().endsWith('.pdf');
    if (!isPdf) {
      showToast('Selecione um arquivo PDF válido.', 'danger');
      return;
    }

    const body = new FormData();
    body.append('turma_id', turmaId);
    body.append('materia_id', materiaId);
    body.append('titulo', titulo);
    body.append('descricao', descricao);
    body.append('arquivo', arquivo);

    await submitRequest('/documentos', {
      method: 'POST',
      body: body
    }, pdfForm, 'Documento publicado com sucesso.', true);
  }

  async function handleRecadoSubmit(event) {
    event.preventDefault();

    const payload = {
      turma_id: selectsTurma.recadoTurma.value,
      titulo: fields.recadoTitulo.value.trim(),
      mensagem: fields.recadoTexto.value.trim()
    };

    if (!payload.turma_id || !payload.titulo || !payload.mensagem) {
      showToast('Informe turma, título e mensagem do recado.', 'warning');
      return;
    }

    await submitJson('/recados', payload, recadoForm, 'Recado publicado com sucesso.');
  }

  async function submitJson(route, payload, form, successMessage) {
    await submitRequest(route, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(payload)
    }, form, successMessage);
  }

  async function submitRequest(route, options, form, successMessage, shouldResetMateria) {
    try {
      await apiRequest(route, options);
      form.reset();
      if (shouldResetMateria) {
        fields.pdfMateria.innerHTML = '';
      }
      await refreshData();
      showToast(successMessage, 'success');
    } catch (error) {
      showToast(error.message || 'Não foi possível salvar os dados.', 'danger');
    }
  }

  function renderAll() {
    populateTurmaSelects();
    updateMateriaByTurma();
    renderTurmasAlunos();
    renderMaterias();
    renderPublicacoes();
  }

  function populateTurmaSelects() {
    const sortedTurmas = state.turmas.slice().sort(function (a, b) {
      return String(a.nome).localeCompare(String(b.nome));
    });

    Object.keys(selectsTurma).forEach(function (key) {
      const select = selectsTurma[key];
      const previousValue = select.value;
      select.innerHTML = '';

      if (!sortedTurmas.length) {
        select.innerHTML = '<option value="">Cadastre uma turma primeiro</option>';
        return;
      }

      sortedTurmas.forEach(function (turma) {
        const option = document.createElement('option');
        option.value = turma.id;
        option.textContent = turma.nome + ' • ' + turma.ano_letivo + ' • ' + turma.turno;
        select.appendChild(option);
      });

      if (previousValue && sortedTurmas.some(function (turma) { return String(turma.id) === String(previousValue); })) {
        select.value = previousValue;
      }
    });
  }

  function updateMateriaByTurma() {
    const turmaId = selectsTurma.pdfTurma.value;
    const previousValue = fields.pdfMateria.value;
    const materiasDaTurma = state.materias.filter(function (materia) {
      return String(materia.turma_id) === String(turmaId);
    });

    fields.pdfMateria.innerHTML = '';

    if (!materiasDaTurma.length) {
      fields.pdfMateria.innerHTML = '<option value="">Cadastre matéria nesta turma</option>';
      return;
    }

    materiasDaTurma.forEach(function (materia) {
      const option = document.createElement('option');
      option.value = materia.id;
      option.textContent = materia.nome;
      fields.pdfMateria.appendChild(option);
    });

    if (previousValue && materiasDaTurma.some(function (materia) { return String(materia.id) === String(previousValue); })) {
      fields.pdfMateria.value = previousValue;
    }
  }

  function renderTurmasAlunos() {
    turmasContainer.innerHTML = '';

    if (!state.turmas.length) {
      turmasContainer.innerHTML = '<p class="text-muted mb-0">Nenhuma turma cadastrada.</p>';
      return;
    }

    state.turmas.forEach(function (turma) {
      const alunosDaTurma = state.alunos.filter(function (aluno) {
        return String(aluno.turma_id) === String(turma.id);
      });

      const item = document.createElement('article');
      item.className = 'border rounded-3 p-3 bg-white';
      item.innerHTML = [
        '<div class="d-flex justify-content-between align-items-center mb-2 gap-2">',
          '<div>',
            '<h3 class="h6 fw-bold mb-1 text-ete-primary">' + escapeHtml(turma.nome) + '</h3>',
            '<p class="small text-muted mb-0">Ano letivo: ' + escapeHtml(turma.ano_letivo) + ' • Turno: ' + escapeHtml(turma.turno) + '</p>',
          '</div>',
          '<span class="badge text-bg-secondary">' + alunosDaTurma.length + ' aluno(s)</span>',
        '</div>',
        turma.descricao ? '<p class="small text-muted mb-2">' + escapeHtml(turma.descricao) + '</p>' : '',
        '<ul class="mb-0 ps-3 small">',
          alunosDaTurma.length
            ? alunosDaTurma.map(function (aluno) {
                return '<li><strong>' + escapeHtml(aluno.nome) + '</strong> — ' + escapeHtml(aluno.email) + ' <span class="text-muted">(' + escapeHtml(aluno.matricula) + ')</span></li>';
              }).join('')
            : '<li class="text-muted">Nenhum aluno cadastrado.</li>',
        '</ul>'
      ].join('');

      turmasContainer.appendChild(item);
    });
  }

  function renderMaterias() {
    materiasContainer.innerHTML = '';

    if (!state.turmas.length) {
      materiasContainer.innerHTML = '<p class="text-muted mb-0">Nenhuma turma cadastrada.</p>';
      return;
    }

    state.turmas.forEach(function (turma) {
      const materiasDaTurma = state.materias.filter(function (materia) {
        return String(materia.turma_id) === String(turma.id);
      });

      const item = document.createElement('div');
      item.className = 'border rounded-3 p-3 bg-white';
      item.innerHTML = [
        '<h3 class="h6 fw-bold mb-2 text-ete-primary">' + escapeHtml(turma.nome) + '</h3>',
        materiasDaTurma.length
          ? '<ul class="small mb-0 ps-3">' + materiasDaTurma.map(function (materia) {
              const descricao = materia.descricao ? ' — <span class="text-muted">' + escapeHtml(materia.descricao) + '</span>' : '';
              return '<li><strong>' + escapeHtml(materia.nome) + '</strong>' + descricao + '</li>';
            }).join('') + '</ul>'
          : '<p class="mb-0 small text-muted">Sem matérias cadastradas.</p>'
      ].join('');
      materiasContainer.appendChild(item);
    });
  }

  function renderPublicacoes() {
    publicacoesContainer.innerHTML = '';

    const publicacoes = state.documentos.map(function (documento) {
      return {
        id: 'documento-' + documento.id,
        tipo: 'pdf',
        data_publicacao: documento.data_publicacao,
        titulo: documento.titulo,
        descricao: documento.descricao,
        arquivo: documento.nome_arquivo,
        turma_nome: documento.turma_nome,
        materia_nome: documento.materia_nome,
        caminho_arquivo: documento.caminho_arquivo
      };
    }).concat(state.recados.map(function (recado) {
      return {
        id: 'recado-' + recado.id,
        tipo: 'recado',
        data_publicacao: recado.data_publicacao,
        titulo: recado.titulo,
        mensagem: recado.mensagem,
        turma_nome: recado.turma_nome
      };
    }));

    publicacoes.sort(function (a, b) {
      return new Date(b.data_publicacao) - new Date(a.data_publicacao);
    });

    if (!publicacoes.length) {
      publicacoesContainer.innerHTML = '<p class="text-muted mb-0">Nenhuma publicação ainda.</p>';
      return;
    }

    publicacoes.forEach(function (publicacao) {
      const card = document.createElement('article');
      card.className = 'border rounded-3 p-3 bg-white';

      if (publicacao.tipo === 'pdf') {
        card.innerHTML = [
          '<div class="d-flex justify-content-between align-items-start gap-2">',
            '<div>',
              '<h3 class="h6 fw-bold mb-1"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>' + escapeHtml(publicacao.titulo) + '</h3>',
              '<p class="mb-1 small text-muted">Turma: ' + escapeHtml(publicacao.turma_nome) + ' | Matéria: ' + escapeHtml(publicacao.materia_nome) + '</p>',
              publicacao.descricao ? '<p class="mb-1 small">' + escapeHtml(publicacao.descricao) + '</p>' : '',
              '<p class="mb-1 small">Arquivo: <a href="' + escapeAttribute(publicacao.caminho_arquivo) + '" target="_blank">' + escapeHtml(publicacao.arquivo) + '</a></p>',
              '<p class="mb-0 small text-muted">Publicado em ' + formatDate(publicacao.data_publicacao) + '</p>',
            '</div>',
            '<span class="badge text-bg-danger">PDF</span>',
          '</div>'
        ].join('');
      } else {
        card.innerHTML = [
          '<div class="d-flex justify-content-between align-items-start gap-2">',
            '<div>',
              '<h3 class="h6 fw-bold mb-1">📢 ' + escapeHtml(publicacao.titulo) + '</h3>',
              '<p class="mb-1 small text-muted">Turma: ' + escapeHtml(publicacao.turma_nome) + '</p>',
              '<p class="mb-1 small">' + escapeHtml(publicacao.mensagem) + '</p>',
              '<p class="mb-0 small text-muted">Publicado em ' + formatDate(publicacao.data_publicacao) + '</p>',
            '</div>',
            '<span class="badge text-bg-warning">Recado</span>',
          '</div>'
        ].join('');
      }

      publicacoesContainer.appendChild(card);
    });
  }

  function renderGlobalError(message) {
    const html = '<div class="alert alert-danger mb-0">' + escapeHtml(message) + '</div>';
    turmasContainer.innerHTML = html;
    materiasContainer.innerHTML = html;
    publicacoesContainer.innerHTML = html;
  }

  function formatDate(value) {
    const date = new Date(value);
    if (isNaN(date.getTime())) {
      return value;
    }

    return date.toLocaleString('pt-BR');
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
