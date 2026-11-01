(function () {
  if (!window.isCurrentPortalFile('professor-trabalho.php')) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const trabalhoId = params.get('id');
  const form = document.getElementById('trabalhoForm');
  const turmaSelect = document.getElementById('trabalhoTurma');
  const aulaSelect = document.getElementById('trabalhoAula');

  function formatarData(valor) {
    const texto = String(valor || '').slice(0, 10);
    const partes = texto.split('-');
    if (partes.length !== 3 || partes[0].length !== 4) {
      return texto;
    }
    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }

  function formatarQuando(valor) {
    const texto = String(valor || '');
    const data = formatarData(texto);
    const hora = texto.length >= 16 ? texto.slice(11, 16) : '';
    return hora ? data + ' às ' + hora : data;
  }

  function rotuloStatus(status) {
    if (status === 'entregue') {
      return 'Entregue';
    }
    if (status === 'entregue_atraso') {
      return 'Entregue com atraso';
    }
    return 'Pendente';
  }

  function classeStatus(status) {
    if (status === 'entregue') {
      return 'text-bg-success';
    }
    if (status === 'entregue_atraso') {
      return 'text-bg-danger';
    }
    return 'text-bg-warning';
  }

  function rotuloEvento(tipo) {
    if (tipo === 'publicado') {
      return 'Publicação';
    }
    if (tipo === 'entrega') {
      return 'Entrega';
    }
    if (tipo === 'feedback') {
      return 'Feedback';
    }
    return 'Status';
  }

  function renderTimeline(eventos) {
    if (!eventos || !eventos.length) {
      return '<p class="text-muted mb-0">Nenhum evento ainda.</p>';
    }

    return '<ol class="list-group list-group-numbered">' + eventos.map(function (evento) {
      let extra = '';
      if (evento.texto) {
        extra += '<p class="mb-1" style="white-space: pre-wrap">' + escapeHtml(evento.texto) + '</p>';
      }
      if (evento.arquivo_caminho) {
        extra += '<p class="mb-1"><a href="' + escapeHtml(evento.arquivo_caminho) + '" target="_blank" rel="noopener">' + escapeHtml(evento.arquivo_nome || 'Anexo') + '</a></p>';
      }
      if (evento.data_entrega) {
        extra += '<p class="mb-1 small text-muted">Data de entrega: ' + escapeHtml(formatarData(evento.data_entrega)) + '</p>';
      }
      const autor = evento.autor_nome ? escapeHtml(evento.autor_nome) + ' · ' : '';
      return '<li class="list-group-item">' +
        '<div class="d-flex justify-content-between gap-3"><strong>' + rotuloEvento(evento.tipo) + '</strong>' +
        '<span class="small text-muted">' + autor + escapeHtml(formatarQuando(evento.created_at)) + '</span></div>' +
        extra + '</li>';
    }).join('') + '</ol>';
  }

  function opcoesStatus(atual) {
    const opcoes = [
      ['pendente', 'Pendente'],
      ['entregue', 'Entregue'],
      ['entregue_atraso', 'Entregue com atraso']
    ];
    return opcoes.map(function (opcao) {
      const selected = opcao[0] === atual ? ' selected' : '';
      return '<option value="' + opcao[0] + '"' + selected + '>' + opcao[1] + '</option>';
    }).join('');
  }

  function renderAlunos(alunos) {
    const lista = document.getElementById('alunosLista');
    if (!alunos.length) {
      lista.innerHTML = '<article class="ete-card p-4"><p class="text-muted mb-0">Nenhum aluno aprovado nesta turma.</p></article>';
      return;
    }

    lista.innerHTML = alunos.map(function (aluno) {
      const entrega = aluno.ultima_entrega;
      let entregaHtml = '<p class="text-muted mb-3">Ainda sem entrega.</p>';
      if (entrega) {
        entregaHtml = '<div class="mb-3"><p class="fw-semibold mb-1">Última entrega · ' + escapeHtml(formatarQuando(entrega.created_at)) + '</p>';
        if (entrega.texto) {
          entregaHtml += '<p class="mb-1" style="white-space: pre-wrap">' + escapeHtml(entrega.texto) + '</p>';
        }
        if (entrega.arquivo_caminho) {
          entregaHtml += '<a href="' + escapeHtml(entrega.arquivo_caminho) + '" target="_blank" rel="noopener">' + escapeHtml(entrega.arquivo_nome || 'Anexo') + '</a>';
        }
        entregaHtml += '</div>';
      }

      return '<article class="ete-card p-4" data-participacao="' + aluno.id + '">' +
        '<div class="d-flex flex-column flex-md-row justify-content-between gap-2 mb-3">' +
        '<div><h2 class="h5 fw-bold mb-1">' + escapeHtml(aluno.aluno_nome) + '</h2>' +
        '<p class="small text-muted mb-0">' + escapeHtml(aluno.aluno_email || '') + ' · entrega vigente ' + escapeHtml(formatarData(aluno.data_entrega_vigente)) + '</p></div>' +
        '<span class="badge ' + classeStatus(aluno.status) + ' align-self-start">' + rotuloStatus(aluno.status) + '</span></div>' +
        entregaHtml +
        '<div class="row g-2 align-items-end mb-3">' +
        '<div class="col-md-6"><label class="form-label small fw-semibold">Status</label>' +
        '<select class="form-select js-status">' + opcoesStatus(aluno.status) + '</select></div>' +
        '<div class="col-md-6"><button type="button" class="btn btn-outline-primary btn-touch js-salvar-status">Salvar status</button></div>' +
        '</div>' +
        '<div class="border-top pt-3 mb-3">' +
        '<h3 class="h6 fw-bold mb-2">Pedir correção</h3>' +
        '<label class="form-label small fw-semibold">Feedback</label>' +
        '<textarea class="form-control mb-2 js-feedback" rows="3" maxlength="5000" placeholder="Descreva o que o aluno deve corrigir"></textarea>' +
        '<div class="row g-2 align-items-end">' +
        '<div class="col-md-6"><label class="form-label small fw-semibold">Nova data de entrega</label>' +
        '<input class="form-control js-nova-data" type="date" /></div>' +
        '<div class="col-md-6"><button type="button" class="btn btn-ete-primary btn-touch js-enviar-feedback">Enviar feedback</button></div>' +
        '</div></div>' +
        '<h3 class="h6 fw-bold mb-2">Linha do tempo</h3>' +
        renderTimeline(aluno.eventos || []) +
        '</article>';
    }).join('');
  }

  function renderMaterial(trabalho) {
    const alvo = document.getElementById('detalheMaterial');
    const partes = [];
    if (trabalho.arquivo_caminho) {
      partes.push('<a class="btn btn-outline-primary btn-touch" href="' + escapeHtml(trabalho.arquivo_caminho) + '" target="_blank" rel="noopener">Abrir PDF</a>');
    }
    if (trabalho.embed_url) {
      partes.push('<div class="ratio ratio-16x9 mt-3"><iframe src="' + escapeHtml(trabalho.embed_url) + '" title="Vídeo do trabalho" allowfullscreen></iframe></div>');
    } else if (trabalho.video_url) {
      partes.push('<p class="mb-0"><a href="' + escapeHtml(trabalho.video_url) + '" target="_blank" rel="noopener">Abrir vídeo</a></p>');
    }
    alvo.innerHTML = partes.length ? partes.join('') : '<p class="text-muted mb-0">Sem PDF ou vídeo.</p>';
  }

  async function carregarDetalhe() {
    document.getElementById('cadastroHero').classList.add('d-none');
    document.getElementById('cadastroCard').classList.add('d-none');
    document.getElementById('detalheSection').classList.remove('d-none');

    const aviso = sessionStorage.getItem('portalAvisoTrabalho');
    if (aviso) {
      sessionStorage.removeItem('portalAvisoTrabalho');
      window.showPortalFeedback(aviso, aviso.indexOf('Não foi possível') !== -1 ? 'warning' : 'success');
    }

    const response = await apiRequest('/trabalhos/detalhe?id=' + encodeURIComponent(trabalhoId));
    const data = response.data || {};
    const trabalho = data.trabalho || {};
    document.getElementById('detalheTitulo').textContent = trabalho.titulo || 'Trabalho';
    document.getElementById('detalheMeta').textContent = (trabalho.turma_codigo || '') + ' — ' + (trabalho.aula_titulo || '');
    document.getElementById('detalheData').textContent = 'Entrega inicial em ' + formatarData(trabalho.data_entrega);
    document.getElementById('detalheDescricao').textContent = trabalho.descricao || '';
    renderMaterial(trabalho);
    renderAlunos(data.alunos || []);
  }

  async function carregarTurmas() {
    const response = await apiRequest('/turmas');
    const turmas = response.data || [];
    turmaSelect.innerHTML = '<option value="">Selecione a turma</option>' + turmas.map(function (turma) {
      return '<option value="' + turma.id + '">' + escapeHtml(turma.codigo) + ' — ' + escapeHtml(turma.titulo) + '</option>';
    }).join('');
  }

  async function carregarAulas() {
    const turmaId = turmaSelect.value;
    aulaSelect.innerHTML = '<option value="">Selecione a aula</option>';
    if (!turmaId) {
      return;
    }
    const response = await apiRequest('/aulas?turma_id=' + encodeURIComponent(turmaId));
    const aulas = response.data || [];
    aulaSelect.innerHTML += aulas.map(function (aula) {
      return '<option value="' + aula.id + '">' + escapeHtml(aula.titulo) + '</option>';
    }).join('');
  }

  if (trabalhoId) {
    carregarDetalhe().catch(function (error) {
      window.showPortalFeedback(error.message, 'danger');
    });
  } else {
    carregarTurmas().catch(function (error) {
      window.showPortalFeedback(error.message, 'danger');
    });
  }

  turmaSelect.addEventListener('change', function () {
    carregarAulas().catch(function (error) {
      window.showPortalFeedback(error.message, 'danger');
    });
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const botao = document.getElementById('salvarTrabalho');
    botao.disabled = true;

    const dados = new FormData();
    dados.append('titulo', document.getElementById('trabalhoTitulo').value.trim());
    dados.append('descricao', document.getElementById('trabalhoDescricao').value.trim());
    dados.append('data_entrega', document.getElementById('trabalhoData').value);
    dados.append('turma_id', turmaSelect.value);
    dados.append('aula_id', aulaSelect.value);
    dados.append('video_url', document.getElementById('trabalhoVideo').value.trim());
    const arquivo = document.getElementById('trabalhoArquivo').files[0];
    if (arquivo) {
      dados.append('arquivo', arquivo);
    }

    try {
      const response = await apiRequest('/trabalhos', {
        method: 'POST',
        body: dados
      });
      sessionStorage.setItem('portalAvisoTrabalho', response.message || 'Trabalho publicado.');
      window.location.href = 'professor-trabalho.php?id=' + encodeURIComponent(response.id);
    } catch (error) {
      window.showPortalFeedback(error.message, 'danger');
      botao.disabled = false;
    }
  });

  document.getElementById('alunosLista').addEventListener('click', async function (event) {
    const card = event.target.closest('[data-participacao]');
    if (!card) {
      return;
    }
    const participacaoId = card.getAttribute('data-participacao');

    if (event.target.closest('.js-salvar-status')) {
      const status = card.querySelector('.js-status').value;
      try {
        const response = await apiRequest('/trabalhos/status', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            trabalho_aluno_id: participacaoId,
            status: status
          })
        });
        window.showPortalFeedback(response.message || 'Status atualizado.', 'success');
        await carregarDetalhe();
      } catch (error) {
        window.showPortalFeedback(error.message, 'danger');
      }
      return;
    }

    if (event.target.closest('.js-enviar-feedback')) {
      const texto = card.querySelector('.js-feedback').value.trim();
      const dataEntrega = card.querySelector('.js-nova-data').value;
      try {
        const response = await apiRequest('/trabalhos/feedback', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            trabalho_aluno_id: participacaoId,
            texto: texto,
            data_entrega: dataEntrega
          })
        });
        window.showPortalFeedback(response.message || 'Feedback registrado.', response.emails_falhas ? 'warning' : 'success');
        await carregarDetalhe();
      } catch (error) {
        window.showPortalFeedback(error.message, 'danger');
      }
    }
  });
})();
