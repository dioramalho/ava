(function () {
  if (!window.isCurrentPortalFile('professor-trabalhos.php')) {
    return;
  }

  const tbody = document.getElementById('trabalhosTableBody');

  function formatarData(valor) {
    const texto = String(valor || '').slice(0, 10);
    const partes = texto.split('-');
    if (partes.length !== 3 || partes[0].length !== 4) {
      return texto;
    }
    return partes[2] + '/' + partes[1] + '/' + partes[0];
  }

  async function carregar() {
    const response = await apiRequest('/trabalhos');
    const lista = response.data || [];

    if (!lista.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="text-muted">Nenhum trabalho publicado.</td></tr>';
      return;
    }

    tbody.innerHTML = lista.map(function (item) {
      const pendentes = parseInt(item.pendentes_count, 10) || 0;
      const alunos = parseInt(item.alunos_count, 10) || 0;
      return '<tr>' +
        '<td><strong>' + escapeHtml(item.titulo) + '</strong></td>' +
        '<td>' + escapeHtml(item.turma_codigo) + '</td>' +
        '<td>' + escapeHtml(item.aula_titulo) + '</td>' +
        '<td>' + escapeHtml(formatarData(item.data_entrega)) + '</td>' +
        '<td>' + pendentes + ' de ' + alunos + '</td>' +
        '<td class="text-end"><a class="btn btn-sm btn-outline-primary" href="professor-trabalho.php?id=' + encodeURIComponent(item.id) + '">Abrir</a></td>' +
        '</tr>';
    }).join('');
  }

  carregar().catch(function (error) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-danger">' + escapeHtml(error.message) + '</td></tr>';
  });
})();
