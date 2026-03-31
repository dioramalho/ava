const loginForm = document.getElementById('loginForm');
const loginAlert = document.getElementById('loginAlert');
const protectedPages = ['dashboard.html', 'dashboard-professor.html', 'professor-alunos.html'];
const currentPage = window.location.pathname.split('/').pop();

if (loginForm) {
  loginForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();

    try {
      await apiRequest('/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          login: username,
          senha: password
        })
      });

      window.location.href = 'dashboard.html';
    } catch (error) {
      if (loginAlert) {
        loginAlert.classList.remove('d-none');
        loginAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + (error.message || 'Login ou senha inválidos');
      }
    }
  });
}

async function validateSession(options) {
  if (!protectedPages.includes(currentPage)) {
    return null;
  }

  const config = options || {};

  try {
    const response = await apiRequest('/auth/me');
    const user = response.data || null;

    if (config.requiredProfile && user && user.perfil !== config.requiredProfile) {
      window.location.href = 'dashboard.html';
      return null;
    }

    return user;
  } catch (error) {
    window.location.href = 'index.html';
    return null;
  }
}

const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', async function () {
    try {
      await apiRequest('/auth/logout', {
        method: 'POST'
      });
    } catch (error) {
      // Em caso de erro, redireciona mesmo assim para evitar sessão presa no front.
    }

    window.location.href = 'index.html';
  });
}

window.validatePortalSession = validateSession;
validateSession();
