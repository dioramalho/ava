(function () {
  var form = document.getElementById('cadastroAlunoForm');
  var alertBox = document.getElementById('cadastroAlert');
  var submitBtn = form ? form.querySelector('button[type="submit"]') : null;

  if (!form) {
    return;
  }

  form.addEventListener('submit', async function (event) {
    event.preventDefault();

    var nome = document.getElementById('nome').value.trim();
    var celular = document.getElementById('celular').value.trim();
    var email = document.getElementById('email').value.trim().toLowerCase();
    var senha = document.getElementById('senha').value;
    var confirmacao = document.getElementById('confirmacaoSenha').value;

    if (senha !== confirmacao) {
      showAlert('As senhas não coincidem.');
      return;
    }

    if (senha.length < 6) {
      showAlert('A senha deve ter pelo menos 6 caracteres.');
      return;
    }

    if (submitBtn) {
      submitBtn.disabled = true;
    }

    try {
      await apiRequest('/auth/cadastro-aluno', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nome: nome,
          celular: celular,
          email: email,
          senha: senha,
          confirmacao_senha: confirmacao
        })
      });
      window.location.href = 'cadastro-enviado.html';
    } catch (error) {
      showAlert(error.message || 'Não foi possível enviar a solicitação.');
      if (submitBtn) {
        submitBtn.disabled = false;
      }
    }
  });

  function showAlert(message) {
    if (!alertBox) {
      return;
    }

    alertBox.className = 'alert alert-danger';
    alertBox.textContent = message;
  }
})();
