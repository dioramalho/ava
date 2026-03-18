// Dashboard do professor (somente front-end): todos os dados ficam no localStorage.
(function () {
  const isProfessorPage = window.location.pathname.endsWith('dashboard-professor.html');
  if (!isProfessorPage) return;

  const storageKey = 'eteProfessorData';
  const state = loadState();

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

  renderAll();

  turmaForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const turmaNome = document.getElementById('turmaNome').value.trim();
    if (!turmaNome) return;

    if (state.turmas.some((t) => t.nome.toLowerCase() === turmaNome.toLowerCase())) {
      showToast('A turma já existe.', 'warning');
      return;
    }

    state.turmas.push({ id: crypto.randomUUID(), nome: turmaNome });
    turmaForm.reset();
    persistAndRender('Turma cadastrada com sucesso.');
  });

  alunoForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const nome = document.getElementById('alunoNome').value.trim();
    const turmaId = selectsTurma.alunoTurma.value;
    const email = document.getElementById('alunoEmail').value.trim();
    if (!nome || !turmaId || !email) return;

    state.alunos.push({ id: crypto.randomUUID(), nome, turmaId, email });
    alunoForm.reset();
    persistAndRender('Aluno cadastrado com sucesso.');
  });

  materiaForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const nome = document.getElementById('materiaNome').value.trim();
    const turmaId = selectsTurma.materiaTurma.value;
    if (!nome || !turmaId) return;

    state.materias.push({ id: crypto.randomUUID(), nome, turmaId });
    materiaForm.reset();
    persistAndRender('Matéria cadastrada com sucesso.');
  });

  pdfForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const turmaId = selectsTurma.pdfTurma.value;
    const materiaId = pdfMateria.value;
    const titulo = document.getElementById('pdfTitulo').value.trim();
    const arquivo = document.getElementById('pdfArquivo').files[0];

    if (!turmaId || !materiaId || !titulo || !arquivo) return;

    const isPdf = arquivo.type === 'application/pdf' || arquivo.name.toLowerCase().endsWith('.pdf');
    if (!isPdf) {
      showToast('Selecione um arquivo PDF válido.', 'danger');
      return;
    }

    state.publicacoes.push({
      id: crypto.randomUUID(),
      turmaId,
      tipo: 'pdf',
      materiaId,
      titulo,
      arquivoNome: arquivo.name,
      data: new Date().toISOString()
    });

    pdfForm.reset();
    updateMateriaByTurma();
    persistAndRender('PDF publicado com sucesso (simulado).');
  });

  recadoForm.addEventListener('submit', function (event) {
    event.preventDefault();
    const turmaId = selectsTurma.recadoTurma.value;
    const texto = document.getElementById('recadoTexto').value.trim();
    if (!turmaId || !texto) return;

    state.publicacoes.push({
      id: crypto.randomUUID(),
      turmaId,
      tipo: 'recado',
      texto,
      data: new Date().toISOString()
    });

    recadoForm.reset();
    persistAndRender('Recado publicado com sucesso.');
  });

  selectsTurma.pdfTurma.addEventListener('change', updateMateriaByTurma);

  function loadState() {
    const saved = localStorage.getItem(storageKey);
    if (saved) return JSON.parse(saved);

    return {
      turmas: [
        { id: 't1', nome: 'TDS 2024' },
        { id: 't2', nome: 'TDS 2025' }
      ],
      alunos: [
        { id: 'a1', nome: 'Ana Lima', turmaId: 't1', email: 'ana.lima@ete.com' },
        { id: 'a2', nome: 'João Silva', turmaId: 't2', email: 'joao.silva@ete.com' }
      ],
      materias: [
        { id: 'm1', nome: 'Algoritmos', turmaId: 't1' },
        { id: 'm2', nome: 'Banco de Dados', turmaId: 't1' },
        { id: 'm3', nome: 'Desenvolvimento Web', turmaId: 't2' }
      ],
      publicacoes: [
        {
          id: 'p1',
          turmaId: 't1',
          tipo: 'recado',
          texto: 'Prova de Algoritmos na próxima semana.',
          data: new Date().toISOString()
        }
      ]
    };
  }

  function persistAndRender(message) {
    localStorage.setItem(storageKey, JSON.stringify(state));
    renderAll();
    if (message) showToast(message, 'success');
  }

  function renderAll() {
    populateTurmaSelects();
    updateMateriaByTurma();
    renderTurmasAlunos();
    renderMaterias();
    renderPublicacoes();
  }

  function populateTurmaSelects() {
    Object.values(selectsTurma).forEach((select) => {
      const previousValue = select.value;
      select.innerHTML = '';
      if (state.turmas.length === 0) {
        select.innerHTML = '<option value="">Cadastre uma turma primeiro</option>';
        return;
      }

      state.turmas.forEach((turma) => {
        const option = document.createElement('option');
        option.value = turma.id;
        option.textContent = turma.nome;
        select.appendChild(option);
      });

      if (previousValue && state.turmas.some((turma) => turma.id === previousValue)) {
        select.value = previousValue;
      }
    });
  }

  function updateMateriaByTurma() {
    const turmaId = selectsTurma.pdfTurma.value;
    const materiasDaTurma = state.materias.filter((materia) => materia.turmaId === turmaId);

    pdfMateria.innerHTML = '';
    if (materiasDaTurma.length === 0) {
      pdfMateria.innerHTML = '<option value="">Cadastre matéria nesta turma</option>';
      return;
    }

    materiasDaTurma.forEach((materia) => {
      const option = document.createElement('option');
      option.value = materia.id;
      option.textContent = materia.nome;
      pdfMateria.appendChild(option);
    });
  }

  function renderTurmasAlunos() {
    turmasContainer.innerHTML = '';
    state.turmas.forEach((turma) => {
      const alunosDaTurma = state.alunos.filter((aluno) => aluno.turmaId === turma.id);
      const item = document.createElement('article');
      item.className = 'border rounded-3 p-3 bg-white';
      item.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h3 class="h6 fw-bold mb-0 text-ete-primary">${turma.nome}</h3>
          <span class="badge text-bg-secondary">${alunosDaTurma.length} aluno(s)</span>
        </div>
        <ul class="mb-0 ps-3 small">
          ${alunosDaTurma.length
            ? alunosDaTurma.map((aluno) => `<li>${aluno.nome} — ${aluno.email}</li>`).join('')
            : '<li class="text-muted">Nenhum aluno cadastrado.</li>'}
        </ul>
      `;
      turmasContainer.appendChild(item);
    });
  }

  function renderMaterias() {
    materiasContainer.innerHTML = '';
    state.turmas.forEach((turma) => {
      const materias = state.materias.filter((materia) => materia.turmaId === turma.id);
      const item = document.createElement('div');
      item.className = 'border rounded-3 p-3 bg-white';
      item.innerHTML = `
        <h3 class="h6 fw-bold mb-2 text-ete-primary">${turma.nome}</h3>
        <p class="mb-0 small">${materias.length ? materias.map((m) => m.nome).join(', ') : 'Sem matérias cadastradas.'}</p>
      `;
      materiasContainer.appendChild(item);
    });
  }

  function renderPublicacoes() {
    publicacoesContainer.innerHTML = '';
    const ordered = [...state.publicacoes].sort((a, b) => new Date(b.data) - new Date(a.data));

    if (ordered.length === 0) {
      publicacoesContainer.innerHTML = '<p class="text-muted mb-0">Nenhuma publicação ainda.</p>';
      return;
    }

    ordered.forEach((pub) => {
      const turma = findTurma(pub.turmaId);
      const materia = pub.materiaId ? findMateria(pub.materiaId) : null;
      const card = document.createElement('article');
      card.className = 'border rounded-3 p-3 bg-white';

      if (pub.tipo === 'pdf') {
        card.innerHTML = `
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <h3 class="h6 fw-bold mb-1"><i class="bi bi-file-earmark-pdf-fill text-danger me-1"></i>${pub.titulo}</h3>
              <p class="mb-1 small text-muted">Turma: ${turma?.nome || '-'} | Matéria: ${materia?.nome || '-'}</p>
              <p class="mb-0 small">Arquivo selecionado: ${pub.arquivoNome}</p>
            </div>
            <span class="badge text-bg-danger">PDF</span>
          </div>
        `;
      } else {
        card.innerHTML = `
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <h3 class="h6 fw-bold mb-1">📢 Recado para ${turma?.nome || '-'}</h3>
              <p class="mb-0 small">${pub.texto}</p>
            </div>
            <span class="badge text-bg-warning">Recado</span>
          </div>
        `;
      }

      publicacoesContainer.appendChild(card);
    });
  }

  function findTurma(id) {
    return state.turmas.find((turma) => turma.id === id);
  }

  function findMateria(id) {
    return state.materias.find((materia) => materia.id === id);
  }

  function showToast(message, variant) {
    const alert = document.createElement('div');
    alert.className = `alert alert-${variant} position-fixed top-0 end-0 m-3 shadow`;
    alert.style.zIndex = '1080';
    alert.textContent = message;
    document.body.appendChild(alert);

    setTimeout(() => {
      alert.remove();
    }, 2500);
  }
})();
