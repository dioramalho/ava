(function () {
  const page = window.currentPortalFile ? window.currentPortalFile() : (window.location.pathname.split('/').pop() || '').toLowerCase();

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

      initTrocaSenha();

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
      renderTrabalhosPendentes(data.trabalhos_pendentes || []);
    } catch (error) {
      if (disciplinasWrap) {
        disciplinasWrap.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">' + escapeHtml(error.message) + '</div></div>';
      }
    }
  }

  function mensagemNovaSenha(senha, confirmacao) {
    if (!senha || !confirmacao) {
      return 'Informe a nova senha e a confirmação.';
    }
    if (senha.length < 6) {
      return 'A senha deve ter pelo menos 6 caracteres.';
    }
    if (senha !== confirmacao) {
      return 'A confirmação de senha não confere.';
    }
    if (senha === '@ETE2626') {
      return 'Escolha uma senha diferente da senha provisória.';
    }
    return '';
  }

  function initTrocaSenha() {
    const form = document.getElementById('formSenha');
    const alertBox = document.getElementById('senhaAlert');
    if (!form || form.getAttribute('data-bound') === '1') {
      return;
    }
    form.setAttribute('data-bound', '1');

    form.addEventListener('submit', async function (event) {
      event.preventDefault();
      if (alertBox) {
        alertBox.className = 'alert d-none';
        alertBox.textContent = '';
      }

      const senhaAtual = document.getElementById('senhaAtual').value.trim();
      const senha = document.getElementById('senhaNova').value.trim();
      const confirmacao = document.getElementById('senhaConfirmacao').value.trim();
      const erro = !senhaAtual ? 'Informe a senha atual.' : mensagemNovaSenha(senha, confirmacao);
      if (erro) {
        if (alertBox) {
          alertBox.className = 'alert alert-danger';
          alertBox.textContent = erro;
        }
        return;
      }

      const button = form.querySelector('button[type="submit"]');
      if (button) {
        button.disabled = true;
      }

      try {
        const response = await apiRequest('/auth/trocar-senha', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            senha_atual: senhaAtual,
            senha: senha,
            confirmacao_senha: confirmacao
          })
        });
        form.reset();
        if (alertBox) {
          alertBox.className = 'alert alert-success';
          alertBox.textContent = response.message || 'Senha alterada com sucesso.';
        }
      } catch (error) {
        if (alertBox) {
          alertBox.className = 'alert alert-danger';
          alertBox.textContent = error.message || 'Não foi possível alterar a senha.';
        }
      } finally {
        if (button) {
          button.disabled = false;
        }
      }
    });
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

  function formatarData(valor) {
    const texto = String(valor || '').slice(0, 10);
    const partes = texto.split('-');
    if (partes.length !== 3 || partes[0].length !== 4) {
      return texto;
    }
    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }

  function renderTrabalhosPendentes(trabalhos) {
    const wrap = document.getElementById('trabalhosAlerta');
    if (!wrap) {
      return;
    }
    if (!trabalhos.length) {
      wrap.innerHTML = '<article class="ete-card p-4"><p class="text-muted mb-0">Nenhum trabalho pendente.</p></article>';
      return;
    }

    wrap.innerHTML = '<div class="alert alert-warning mb-3">Você tem ' + trabalhos.length + (trabalhos.length === 1 ? ' trabalho pendente.' : ' trabalhos pendentes.') + '</div>' +
      '<ul class="list-group">' + trabalhos.map(function (item) {
        return '<li class="list-group-item d-flex justify-content-between align-items-center gap-3">' +
          '<div><strong>' + escapeHtml(item.titulo) + '</strong>' +
          '<p class="small text-muted mb-0">' + escapeHtml(item.aula_titulo || '') + ' · entrega ' + escapeHtml(formatarData(item.data_entrega_vigente)) + '</p></div>' +
          '<a class="btn btn-sm btn-ete-primary" href="aluno-trabalho.html?id=' + encodeURIComponent(item.id) + '">Abrir</a></li>';
      }).join('') + '</ul>';
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
