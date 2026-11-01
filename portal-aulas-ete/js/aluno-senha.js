(function () {
  if (!window.isCurrentPortalFile('aluno-senha.html')) {
    return;
  }

  const form = document.getElementById('formSenhaObrigatoria');
  const alertBox = document.getElementById('senhaAlert');
  if (!form) {
    return;
  }

  function mostrarErro(mensagem) {
    if (!alertBox) {
      return;
    }
    alertBox.classList.remove('d-none');
    alertBox.textContent = mensagem;
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

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    if (alertBox) {
      alertBox.classList.add('d-none');
      alertBox.textContent = '';
    }

    const senha = document.getElementById('senhaNova').value.trim();
    const confirmacao = document.getElementById('senhaConfirmacao').value.trim();
    const erro = mensagemNovaSenha(senha, confirmacao);
    if (erro) {
      mostrarErro(erro);
      return;
    }

    const button = form.querySelector('button[type="submit"]');
    if (button) {
      button.disabled = true;
    }

    try {
      await apiRequest('/auth/trocar-senha', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          senha: senha,
          confirmacao_senha: confirmacao
        })
      });
      window.location.href = 'dashboard.html';
    } catch (error) {
      mostrarErro(error.message || 'Não foi possível alterar a senha.');
      if (button) {
        button.disabled = false;
      }
    }
  });
})();
