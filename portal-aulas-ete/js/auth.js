// Credenciais simuladas para demonstração front-end.
const FAKE_USER = {
  username: 'professor',
  password: '1234'
};

// Controle do formulário de login.
const loginForm = document.getElementById('loginForm');
if (loginForm) {
  loginForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const alertBox = document.getElementById('loginAlert');

    const username = usernameInput.value.trim();
    const password = passwordInput.value.trim();

    if (username === FAKE_USER.username && password === FAKE_USER.password) {
      localStorage.setItem('eteAuth', 'true');
      window.location.href = 'dashboard.html';
      return;
    }

    alertBox.classList.remove('d-none');
  });
}

// Proteção simples para página interna.
const protectedPages = ['dashboard.html', 'dashboard-professor.html'];
const currentPage = window.location.pathname.split('/').pop();
if (protectedPages.includes(currentPage) && localStorage.getItem('eteAuth') !== 'true') {
const isDashboard = window.location.pathname.endsWith('dashboard.html');
if (isDashboard && localStorage.getItem('eteAuth') !== 'true') {
  window.location.href = 'index.html';
}

// Logout simples removendo estado local.
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', function () {
    localStorage.removeItem('eteAuth');
    window.location.href = 'index.html';
  });
}
