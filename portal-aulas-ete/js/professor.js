(function () {
  const isProfessorPage = window.location.pathname.endsWith('dashboard-professor.html');
  if (!isProfessorPage) {
    return;
  }

  const turmaForm = document.getElementById('turmaForm');
  const alunoForm = document.getElementById('alunoForm');
  const materiaForm = document.getElementById('materiaForm');
  const pdfForm = document.getElementById('pdfForm');
  const recadoForm = document.getElementById('recadoForm');

  const selectsTurma = {
    alunoTurma: document.getElementById('alunoTurma'),
    materiaTurma: document.getElementById('materiaTurma'),
    pdfTurma: document.getElementById('pdfTurma'),
    recadoTurma: document.getElementById('recadoTurma')
  };

  const pdfMateria = document.getElementById('pdfMateria');
  const turmasContainer = document.getElementById('turmasContainer');
  const materiasContainer = document.getElementById('materiasContainer');
  const publicacoesContainer = document.getElementById('publicacoesContainer');

  const state = {
    turmas: [],
    alunos: [],
    materias: [],
    recados: [],
    documentos: []
  };

  initialize();

  async function initialize() {
    try {
      await loadData();
      bindEvents();
      renderAll();
    } catch (error) {
      handleApiError(error);
    }
  }

  function bindEvents() {
    turmaForm.addEventListener('submit', handleTurmaSubmit);
    alunoForm.addEventListener('submit', handleAlunoSubmit);
    materiaForm.addEventListener('submit', handleMateriaSubmit);
    pdfForm.addEventListener('submit', handlePdfSubmit);
    recadoForm.addEventListener('submit', handleRecadoSubmit);
    selectsTurma.pdfTurma.addEventListener('change', updateMateriaByTurma);
  }

  async function loadData() {
    const results = await Promise.all([
      apiRequest('/turmas'),
      apiRequest('/alunos'),
      apiRequest('/materias'),
      apiRequest('/recados'),
      apiRequest('/documentos')
    ]);

    state.turmas = results[0].data || [];
    state.alunos = results[1].data || [];
    state.materias = results[2].data || [];
    state.recados = results[3].data || [];
    state.documentos = results[4].data || [];
  }

  async function handleTurmaSubmit(event) {
    event.preventDefault();

    try {
      await apiRequest('/turmas', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          nome: document.getElementById('turmaNome').value.trim(),
          ano_letivo: document.getElementById('turmaAno').value.trim(),
          turno: document.getElementById('turmaTurno').value,
          descricao: document.getElementById('turmaDescricao').value.trim()
        })
      });

      turmaForm.reset();
      await refreshData('Turma cadastrada com sucesso.');
    } catch (error) {
      handleApiError(error);
    }
  }

  async function handleAlunoSubmit(event) {
    event.preventDefault();

    try {
      await apiRequest('/alunos', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          nome: document.getElementById('alunoNome').value.trim(),
          turma_id: selectsTurma.alunoTurma.value,
          email: document.getElementById('alunoEmail').value.trim(),
          matricula: document.getElementById('alunoMatricula').value.trim()
        })
      });

      alunoForm.reset();
      await refreshData('Aluno cadastrado com sucesso.');
    } catch (error) {
      handleApiError(error);
    }
  }

  async function handleMateriaSubmit(event) {
    event.preventDefault();

    try {
      await apiRequest('/materias', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          nome: document.getElementById('materiaNome').value.trim(),
          turma_id: selectsTurma.materiaTurma.value,
          descricao: document.getElementById('materiaDescricao').value.trim()
        })
      });

      materiaForm.reset();
      await refreshData('Matéria cadastrada com sucesso.');
    } catch (error) {
      handleApiError(error);
    }
  }

  async function handlePdfSubmit(event) {
    event.preventDefault();

    try {
      const formData = new FormData();
      formData.append('turma_id', selectsTurma.pdfTurma.value);
      formData.append('materia_id', pdfMateria.value);
      formData.append('titulo', document.getElementById('pdfTitulo').value.trim());
      formData.append('descricao', document.getElementById('pdfDescricao').value.trim());
      formData.append('arquivo', document.getElementById('pdfArquivo').files[0]);

      await apiRequest('/documentos', {
        method: 'POST',
        body: formData
      });

      pdfForm.reset();
      await refreshData('Documento publicado com sucesso.');
    } catch (error) {
      handleApiError(error);
    }
  }

  async function handleRecadoSubmit(event) {
    event.preventDefault();

    try {
      await apiRequest('/recados', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          turma_id: selectsTurma.recadoTurma.value,
          titulo: document.getElementById('recadoTitulo').value.trim(),
          mensagem: document.getElementById('recadoTexto').value.trim()
        })
      });

      recadoForm.reset();
      await refreshData('Recado publicado com sucesso.');
    } catch (error) {
      handleApiError(error);
    }
  }

  async function refreshData(message) {
    await loadData();
    renderAll();
    showToast(message, 'success');
  }

  function renderAll() {
    populateTurmaSelects();
    updateMateriaByTurma();
    renderTurmasAlunos();
    renderMaterias();
    renderPublicacoes();
  }

  function populateTurmaSelects() {
    Object.keys(selectsTurma).forEach(function (key) {
      const select = selectsTurma[key];
      const previousValue = select.value;
      select.innerHTML = '';

      if (!state.turmas.length) {
        select.innerHTML = '<option value="">Cadastre uma turma primeiro</option>';
        return;
      }

      state.turmas.forEach(function (turma) {
        const option = document.createElement('option');
        option.value = turma.id;
        option.textContent = turma.nome + ' (' + turma.ano_letivo + ')';
        select.appendChild(option);
      });

      if (previousValue && state.turmas.some(function (turma) { return String(turma.id) === previousValue; })) {
        select.value = previousValue;
      }
    });
  }

  function updateMateriaByTurma() {
    const turmaId = selectsTurma.pdfTurma.value;
    const materiasDaTurma = state.materias.filter(function (materia) {
      return String(materia.turma_id) === String(turmaId);
    });

    pdfMateria.innerHTML = '';

    if (!materiasDaTurma.length) {
      pdfMateria.innerHTML = '<option value="">Cadastre matéria nesta turma</option>';
      return;
    }

    materiasDaTurma.forEach(function (materia) {
      const option = document.createElement('option');
      option.value = materia.id;
      option.textContent = materia.nome;
      pdfMateria.appendChild(option);
    });
  }

  function renderTurmasAlunos() {
    turmasContainer.innerHTML = '';

    state.turmas.forEach(function (turma) {
      const alunosDaTurma = state.alunos.filter(function (aluno) {
        return String(aluno.turma_id) === String(turma.id);
      });

      const item = document.createElement('article');
      item.className = 'border rounded-3 p-3 bg-white';
      item.innerHTML =
        '<div class="d-flex justify-content-between align-items-center mb-2">' +
          '<h3 class="h6 fw-bold mb-0 text-ete-primary">' + turma.nome + ' (' + turma.ano_letivo + ')</h3>' +
          '<span class="badge text-bg-secondary">' + alunosDaTurma.length + ' aluno(s)</span>' +
        '</div>' +
        '<p class="small text-muted mb-2">Turno: ' + turma.turno + '</p>' +
        '<ul class="mb-0 ps-3 small">' +
          (alunosDaTurma.length
            ? alunosDaTurma.map(function (aluno) {
                return '<li>' + aluno.nome + ' — ' + aluno.email + ' — Matrícula: ' + aluno.matricula + '</li>';
              }).join('')
            : '<li class="text-muted">Nenhum aluno cadastrado.</li>') +
        '</ul>';
      turmasContainer.appendChild(item);
    });
  }

  function renderMaterias() {
    materiasContainer.innerHTML = '';

    state.turmas.forEach(function (turma) {
      const materias = state.materias.filter(function (materia) {
        return String(materia.turma_id) === String(turma.id);
      });

      const item = document.createElement('div');
      item.className = 'border rounded-3 p-3 bg-white';
      item.innerHTML =
        '<h3 class="h6 fw-bold mb-2 text-ete-primary">' + turma.nome + ' (' + turma.ano_letivo + ')</h3>' +
        '<p class="mb-0 small">' +
          (materias.length
            ? materias.map(function (materia) {
                return materia.nome;
              }).join(', ')
            : 'Sem matérias cadastradas.') +
        '</p>';
      materiasContainer.appendChild(item);
    });
  }

  function renderPublicacoes() {
    publicacoesContainer.innerHTML = '';

    const merged = [];

    state.documentos.forEach(function (documento) {
      merged.push({
        tipo: 'pdf',
        data_publicacao: documento.data_publicacao,
        titulo: documento.titulo,
        turma_nome: documento.turma_nome,
        materia_nome: documento.materia_nome,
        nome_arquivo: documento.nome_arquivo
      });
    });

    state.recados.forEach(function (recado) {
      merged.push({
        tipo: 'recado',
        data_publicacao: recado.data_publicacao,
        titulo: recado.titulo,
        turma_nome: recado.turma_nome,
        mensagem: recado.mensagem
      });
    });

    merged.sort(function (a, b) {
      return new Date(b.data_publicacao) - new Date(a.data_publicacao);
    });

    if (!merged.length) {
      publicacoesContainer.innerHTML = '<p class="text-muted mb-0">Nenhuma publicação ainda.</p>';
      return;
    }

    merged.forEach(function (item) {
      const card = document.createElement('article');
      card.className = 'border rounded-3 p-3 bg-white';

      if (item.tipo === 'pdf') {
        card.innerHTML =
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
            '<div>' +
              '<h3 class="h6 fw-bold mb-1"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>' + item.titulo + '</h3>' +
              '<p class="mb-1 small text-muted">Turma: ' + item.turma_nome + ' | Matéria: ' + item.materia_nome + '</p>' +
              '<p class="mb-0 small">Arquivo enviado: ' + item.nome_arquivo + '</p>' +
            '</div>' +
            '<span class="badge text-bg-danger">PDF</span>' +
          '</div>';
      } else {
        card.innerHTML =
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
            '<div>' +
              '<h3 class="h6 fw-bold mb-1">📢 ' + item.titulo + '</h3>' +
              '<p class="mb-1 small text-muted">Turma: ' + item.turma_nome + '</p>' +
              '<p class="mb-0 small">' + item.mensagem + '</p>' +
            '</div>' +
            '<span class="badge text-bg-warning">Recado</span>' +
          '</div>';
      }

      publicacoesContainer.appendChild(card);
    });
  }

  function handleApiError(error) {
    if (error.status === 401) {
      window.location.href = 'index.html';
      return;
    }

    showToast(error.message || 'Não foi possível concluir a operação.', 'danger');
  }

  function showToast(message, variant) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-' + variant + ' position-fixed top-0 end-0 m-3 shadow';
    alert.style.zIndex = '1080';
    alert.textContent = message;
    document.body.appendChild(alert);

    setTimeout(function () {
      alert.remove();
    }, 3000);
  }
})();
