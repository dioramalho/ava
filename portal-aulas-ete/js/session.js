const loginForm = document.getElementById('loginForm');
const loginAlert = document.getElementById('loginAlert');
const currentPage = window.location.pathname.split('/').pop();

const professorPages = [
  'dashboard-professor.html',
  'professor-alunos.html',
  'professor-turmas.html',
  'professor-disciplinas.html',
  'professor-aulas.html',
  'professor-aula.html'
];

const studentPages = [
  'dashboard.html',
  'aluno-aulas.html',
  'aluno-aula.html'
];

if (loginForm) {
  loginForm.addEventListener('submit', async function (event) {
    event.preventDefault();

    if (loginAlert) {
      loginAlert.classList.add('d-none');
    }

    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value.trim();

    try {
      const response = await apiRequest('/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          login: username,
          senha: password
        })
      });

      const user = response.data || {};
      window.location.href = user.perfil === 'professor' ? 'dashboard-professor.html' : 'dashboard.html';
    } catch (error) {
      const code = error.payload && error.payload.code;
      if (code === 'aluno_pendente') {
        window.location.href = 'aluno-aguardando.html';
        return;
      }
      if (code === 'aluno_recusado') {
        window.location.href = 'aluno-recusado.html';
        return;
      }
      if (loginAlert) {
        loginAlert.classList.remove('d-none');
        loginAlert.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + (error.message || 'Login ou senha inválidos');
      }
    }
  });
}

async function validatePortalSession(options) {
  const config = options || {};

  try {
    const response = await apiRequest('/auth/me');
    const user = response.data || null;

    if (config.requiredProfile && user && user.perfil !== config.requiredProfile) {
      window.location.href = user.perfil === 'professor' ? 'dashboard-professor.html' : 'dashboard.html';
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
      // continua o redirect
    }

    window.location.href = 'index.html';
  });
}

window.validatePortalSession = validatePortalSession;

if (professorPages.indexOf(currentPage) !== -1) {
  validatePortalSession({ requiredProfile: 'professor' });
}

if (studentPages.indexOf(currentPage) !== -1) {
  validatePortalSession({ requiredProfile: 'aluno' });
}
