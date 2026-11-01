(function () {
  if (!window.isCurrentPortalFile('aluno-trabalho.html')) {
    return;
  }

  const params = new URLSearchParams(window.location.search);
  const trabalhoId = params.get('id');
  const form = document.getElementById('entregaForm');

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
    const alvo = document.getElementById('linhaTempo');
    if (!eventos || !eventos.length) {
      alvo.innerHTML = '<p class="text-muted mb-0">Nenhum evento ainda.</p>';
      return;
    }

    alvo.innerHTML = '<ol class="list-group list-group-numbered">' + eventos.map(function (evento) {
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
      return '<li class="list-group-item">' +
        '<div class="d-flex justify-content-between gap-3"><strong>' + rotuloEvento(evento.tipo) + '</strong>' +
        '<span class="small text-muted">' + escapeHtml(formatarQuando(evento.created_at)) + '</span></div>' +
        extra + '</li>';
    }).join('') + '</ol>';
  }

  function renderMaterial(trabalho) {
    const alvo = document.getElementById('trabalhoMaterial');
    const partes = [];
    if (trabalho.arquivo_caminho) {
      partes.push('<a class="btn btn-outline-primary btn-touch" href="' + escapeHtml(trabalho.arquivo_caminho) + '" target="_blank" rel="noopener">Abrir PDF</a>');
    }
    if (trabalho.embed_url) {
      partes.push('<div class="ratio ratio-16x9 mt-3"><iframe src="' + escapeHtml(trabalho.embed_url) + '" title="Vídeo do trabalho" allowfullscreen></iframe></div>');
    } else if (trabalho.video_url) {
      partes.push('<p class="mb-0 mt-3"><a href="' + escapeHtml(trabalho.video_url) + '" target="_blank" rel="noopener">Abrir vídeo</a></p>');
    }
    alvo.innerHTML = partes.join('');
  }

  async function carregar() {
    const session = await window.validatePortalSession({ requiredProfile: 'aluno' });
    if (!session) {
      return;
    }
    if (!trabalhoId) {
      throw new Error('Trabalho não informado.');
    }

    const response = await apiRequest('/aluno/trabalhos/detalhe?id=' + encodeURIComponent(trabalhoId));
    const data = response.data || {};
    const trabalho = data.trabalho || {};
    const participacao = data.participacao || {};
    const status = participacao.status || 'pendente';
    const badge = document.getElementById('trabalhoStatus');

    document.getElementById('trabalhoTitulo').textContent = trabalho.titulo || 'Trabalho';
    document.getElementById('trabalhoMeta').textContent = (trabalho.disciplina_titulo || '') + ' · ' + (trabalho.aula_titulo || '');
    document.getElementById('trabalhoData').textContent = 'Entrega em ' + formatarData(participacao.data_entrega_vigente || trabalho.data_entrega);
    document.getElementById('trabalhoDescricao').textContent = trabalho.descricao || '';
    badge.textContent = rotuloStatus(status);
    badge.className = 'badge ' + classeStatus(status);
    renderMaterial(trabalho);
    renderTimeline(participacao.eventos || []);

    const card = document.getElementById('entregaCard');
    if (status !== 'pendente') {
      card.querySelector('form').classList.add('d-none');
      if (!document.getElementById('entregaBloqueada')) {
        const aviso = document.createElement('p');
        aviso.id = 'entregaBloqueada';
        aviso.className = 'text-muted mb-0';
        aviso.textContent = 'Esta entrega já foi registrada. Uma nova resposta fica disponível quando o professor pedir correção.';
        card.appendChild(aviso);
      }
    } else {
      card.querySelector('form').classList.remove('d-none');
      const aviso = document.getElementById('entregaBloqueada');
      if (aviso) {
        aviso.remove();
      }
    }
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    const botao = document.getElementById('enviarEntrega');
    botao.disabled = true;

    const dados = new FormData();
    dados.append('trabalho_id', trabalhoId);
    dados.append('observacoes', document.getElementById('observacoes').value.trim());
    const arquivo = document.getElementById('arquivoResposta').files[0];
    if (arquivo) {
      dados.append('arquivo', arquivo);
    }

    try {
      const response = await apiRequest('/aluno/trabalhos/entregar', {
        method: 'POST',
        body: dados
      });
      window.showPortalFeedback(response.message || 'Entrega registrada.', 'success');
      form.reset();
      await carregar();
    } catch (error) {
      window.showPortalFeedback(error.message, 'danger');
    }

    botao.disabled = false;
  });

  carregar().catch(function (error) {
    document.getElementById('trabalhoMeta').textContent = error.message;
    window.showPortalFeedback(error.message, 'danger');
  });
})();
