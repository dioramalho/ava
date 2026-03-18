const loginForm = document.getElementById('loginForm');
const loginAlert = document.getElementById('loginAlert');
const protectedPages = ['dashboard.html', 'dashboard-professor.html'];
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
        loginAlert.textContent = error.message || 'Login ou senha inválidos';
      }
    }
  });
}

async function validateSession() {
  if (!protectedPages.includes(currentPage)) {
    return;
  }

  try {
    await apiRequest('/auth/me');
  } catch (error) {
    window.location.href = 'index.html';
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
      // Mesmo com falha, segue para a tela de login.
    }

    window.location.href = 'index.html';
  });
}

validateSession();
