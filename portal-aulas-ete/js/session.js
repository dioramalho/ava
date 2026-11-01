const loginForm = document.getElementById('loginForm');
const loginAlert = document.getElementById('loginAlert');
const currentPage = window.currentPortalFile ? window.currentPortalFile() : (window.location.pathname.split('/').pop() || '').toLowerCase();

const professorPages = [
  'dashboard-professor.html',
  'professor-alunos.php',
  'professor-turmas.php',
  'professor-disciplinas.php',
  'professor-aulas.php',
  'professor-aula.php',
  'professor-trabalhos.php',
  'professor-trabalho.php'
];

const studentPages = [
  'dashboard.html',
  'aluno-aulas.html',
  'aluno-aula.html',
  'aluno-trabalhos.html',
  'aluno-trabalho.html'
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
      window.location.href = portalHome(user);
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
      window.location.href = portalHome(user);
      return null;
    }

    if (user && user.perfil === 'aluno' && user.precisa_trocar_senha && !config.allowDefaultPassword) {
      window.location.href = 'aluno-senha.html';
      return null;
    }

    if (config.leavePasswordGate && user && !user.precisa_trocar_senha) {
      window.location.href = portalHome(user);
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

function isListedPortalPage(list) {
  if (typeof window.isCurrentPortalFile === 'function') {
    return list.some(function (name) {
      return window.isCurrentPortalFile(name);
    });
  }
  return list.indexOf(currentPage) !== -1;
}

if (isListedPortalPage(professorPages)) {
  validatePortalSession({ requiredProfile: 'professor' });
}

if (isListedPortalPage(studentPages)) {
  validatePortalSession({ requiredProfile: 'aluno' });
}

if (isCurrentPortalFile('aluno-senha.html')) {
  validatePortalSession({
    requiredProfile: 'aluno',
    allowDefaultPassword: true,
    leavePasswordGate: true
  });
}

function portalHome(user) {
  if (user && user.perfil === 'professor') {
    return 'dashboard-professor.html';
  }
  if (user && user.precisa_trocar_senha) {
    return 'aluno-senha.html';
  }
  return 'dashboard.html';
}
