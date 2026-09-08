(function () {
  const page = window.location.pathname.split('/').pop();

  function firstName(nome) {
    const parts = String(nome || '').trim().split(/\s+/);
    return parts[0] || 'aluno';
  }

  function materialLabel(videos, arquivos) {
    const v = parseInt(videos, 10) || 0;
    const a = parseInt(arquivos, 10) || 0;
    const videoTxt = v === 1 ? '1 vídeo' : v + ' vídeos';
    const arquivoTxt = a === 1 ? '1 arquivo' : a + ' arquivos';
    return videoTxt + ' · ' + arquivoTxt;
  }

  if (page === 'dashboard.html') {
    initHome();
  } else if (page === 'aluno-aulas.html') {
    initLista();
  } else if (page === 'aluno-aula.html') {
    initDetalhe();
  }

  async function initHome() {
    const heroNome = document.getElementById('alunoNome');
    const heroTurma = document.getElementById('alunoTurma');
    const disciplinasWrap = document.getElementById('disciplinasGrid');
    const recentesWrap = document.getElementById('aulasRecentes');

    try {
      const session = await window.validatePortalSession({ requiredProfile: 'aluno' });
      if (!session) {
        return;
      }

      const response = await apiRequest('/aluno/painel');
      const data = response.data || {};
      const aluno = data.aluno || session;

      if (heroNome) {
        heroNome.textContent = 'Olá, ' + firstName(aluno.nome);
      }
      if (heroTurma) {
        const codigo = aluno.turma_codigo || '';
        const titulo = aluno.turma_titulo || '';
        heroTurma.innerHTML = titulo
          ? 'Turma <strong>' + escapeHtml(codigo) + '</strong> — ' + escapeHtml(titulo)
          : 'Turma ainda não vinculada.';
      }

      renderDisciplinas(disciplinasWrap, data.disciplinas || []);
      renderRecentes(recentesWrap, data.aulas_recentes || []);
    } catch (error) {
      if (disciplinasWrap) {
        disciplinasWrap.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">' + escapeHtml(error.message) + '</div></div>';
      }
    }
  }

  function renderDisciplinas(wrap, disciplinas) {
    if (!wrap) {
      return;
    }
    if (!disciplinas.length) {
      wrap.innerHTML = '<div class="col-12"><p class="text-muted mb-0">Nenhuma aula publicada para a sua turma ainda.</p></div>';
      return;
    }

    wrap.innerHTML = disciplinas.map(function (item) {
      const count = parseInt(item.aulas_count, 10) || 0;
      const label = count === 1 ? '1 aula' : count + ' aulas';
      return '<div class="col-md-4">' +
        '<a href="aluno-aulas.html?disciplina_id=' + encodeURIComponent(item.id) + '" class="text-decoration-none">' +
        '<article class="ete-card ete-stat h-100 p-4">' +
        '<p class="text-muted small mb-1">' + escapeHtml(item.codigo) + '</p>' +
        '<h3 class="h6 fw-bold text-ete-primary mb-2">' + escapeHtml(item.titulo) + '</h3>' +
        '<span class="small text-muted">' + escapeHtml(label) + '</span>' +
        '</article></a></div>';
    }).join('');
  }

  function renderRecentes(wrap, aulas) {
    if (!wrap) {
      return;
    }
    if (!aulas.length) {
      wrap.innerHTML = '<li class="list-group-item px-0 text-muted">Nenhuma aula recente.</li>';
      return;
    }

    wrap.innerHTML = aulas.map(function (aula) {
      return '<li class="list-group-item px-0">' +
        '<a href="aluno-aula.html?id=' + aula.id + '" class="d-flex justify-content-between gap-3 text-decoration-none text-reset">' +
        '<div><strong>' + escapeHtml(aula.titulo) + '</strong>' +
        '<p class="small text-muted mb-0">' + escapeHtml(aula.disciplina_titulo) + ' · ' +
        escapeHtml(materialLabel(aula.videos_count, aula.arquivos_count)) + '</p></div>' +
        '<span class="align-self-center small fw-semibold" style="color: var(--ete-navy)">Abrir</span></a></li>';
    }).join('');
  }

  async function initLista() {
    const filtro = document.getElementById('filtroDisciplina');
    const tbody = document.getElementById('aulasTableBody');
    const heroTurma = document.getElementById('listaTurmaHint');
    const params = new URLSearchParams(window.location.search);
    const disciplinaQuery = params.get('disciplina_id') || '';

    try {
      const session = await window.validatePortalSession({ requiredProfile: 'aluno' });
      if (!session) {
        return;
      }

      if (heroTurma) {
        heroTurma.textContent = session.turma_codigo
          ? 'Conteúdo publicado para a turma ' + session.turma_codigo + '. Filtre por disciplina quando quiser.'
          : 'Filtre por disciplina quando quiser.';
      }

      const painel = await apiRequest('/aluno/painel');
      const disciplinas = (painel.data && painel.data.disciplinas) || [];
      filtro.innerHTML = '<option value="">Todas as disciplinas</option>' + disciplinas.map(function (item) {
        return '<option value="' + item.id + '">' + escapeHtml(item.titulo) + '</option>';
      }).join('');
      if (disciplinaQuery) {
        filtro.value = disciplinaQuery;
      }

      filtro.addEventListener('change', function () {
        loadAulas(filtro.value, tbody);
      });
      await loadAulas(filtro.value, tbody);
    } catch (error) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
    }
  }

  async function loadAulas(disciplinaId, tbody) {
    let route = '/aulas';
    if (disciplinaId) {
      route += '?disciplina_id=' + encodeURIComponent(disciplinaId);
    }
    const response = await apiRequest(route);
    const aulas = response.data || [];
    if (!aulas.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-muted">Nenhuma aula publicada para a sua turma.</td></tr>';
      return;
    }

    tbody.innerHTML = aulas.map(function (aula) {
      return '<tr>' +
        '<td><strong>' + escapeHtml(aula.titulo) + '</strong></td>' +
        '<td>' + escapeHtml(aula.disciplina_titulo) + '</td>' +
        '<td><span class="small">' + escapeHtml(materialLabel(aula.videos_count, aula.arquivos_count)) + '</span></td>' +
        '<td class="text-end"><a class="btn btn-sm btn-ete-primary" href="aluno-aula.html?id=' + aula.id + '">Abrir</a></td>' +
        '</tr>';
    }).join('');
  }

  async function initDetalhe() {
    const heroMeta = document.getElementById('aulaMeta');
    const heroTitulo = document.getElementById('aulaTitulo');
    const videosWrap = document.getElementById('aulaVideos');
    const arquivosWrap = document.getElementById('aulaArquivos');
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    try {
      const session = await window.validatePortalSession({ requiredProfile: 'aluno' });
      if (!session) {
        return;
      }

      if (!id) {
        throw new Error('Aula não encontrada.');
      }

      const response = await apiRequest('/aulas/detalhe?id=' + encodeURIComponent(id));
      const aula = response.data || {};

      if (heroMeta) {
        heroMeta.textContent = (aula.turma_codigo || '') + ' · ' + (aula.disciplina_titulo || '');
      }
      if (heroTitulo) {
        heroTitulo.textContent = aula.titulo || 'Aula';
      }

      renderVideos(videosWrap, aula.videos || []);
      renderArquivos(arquivosWrap, aula.arquivos || []);
    } catch (error) {
      if (heroTitulo) {
        heroTitulo.textContent = 'Não foi possível abrir a aula';
      }
      if (heroMeta) {
        heroMeta.textContent = '';
      }
      if (videosWrap) {
        videosWrap.innerHTML = '<div class="alert alert-danger mb-0">' + escapeHtml(error.message) + '</div>';
      }
      if (arquivosWrap) {
        arquivosWrap.innerHTML = '';
      }
    }
  }

  function renderVideos(wrap, videos) {
    if (!wrap) {
      return;
    }
    const embeds = videos.filter(function (video) {
      return video.embed_url;
    });
    if (!embeds.length) {
      wrap.innerHTML = '<p class="text-muted mb-0">Nenhum vídeo publicado nesta aula.</p>';
      return;
    }

    wrap.innerHTML = embeds.map(function (video, index) {
      return '<div class="ete-video' + (index < embeds.length - 1 ? ' mb-3' : '') + '">' +
        '<iframe src="' + escapeHtml(video.embed_url) + '" title="Vídeo da aula" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>' +
        '</div>';
    }).join('');
  }

  function renderArquivos(wrap, arquivos) {
    if (!wrap) {
      return;
    }
    if (!arquivos.length) {
      wrap.innerHTML = '<p class="text-muted mb-0">Nenhum arquivo nesta aula.</p>';
      return;
    }

    wrap.innerHTML = arquivos.map(function (arquivo) {
      const ext = String(arquivo.extensao || '').toLowerCase();
      const icon = ext === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-word text-primary';
      return '<li><a class="btn btn-outline-secondary w-100 text-start btn-touch" href="' +
        escapeHtml(arquivo.caminho_arquivo) + '" target="_blank" rel="noopener">' +
        '<i class="bi ' + icon + ' me-2"></i>' + escapeHtml(arquivo.nome_original) + '</a></li>';
    }).join('');
  }
})();
