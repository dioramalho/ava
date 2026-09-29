const API_BASE_URL = 'backend/public/index.php?route=';

async function apiRequest(route, options) {
  const requestOptions = options || {};
  const isFormData = typeof FormData !== 'undefined' && requestOptions.body instanceof FormData;
  const finalOptions = {
    method: requestOptions.method || 'GET',
    credentials: 'same-origin',
    headers: isFormData ? {} : (requestOptions.headers || {})
  };

  if (requestOptions.body) {
    finalOptions.body = requestOptions.body;
  }

  const qPos = route.indexOf('?');
  let path = route;
  let extraQuery = '';
  if (qPos !== -1) {
    path = route.slice(0, qPos);
    extraQuery = '&' + route.slice(qPos + 1);
  }

  const response = await fetch(API_BASE_URL + path + extraQuery, finalOptions);
  const data = await response.json().catch(function () {
    return {
      success: false,
      message: 'Resposta inválida do servidor.'
    };
  });

  if (!response.ok) {
    const error = new Error(data.message || 'Erro na comunicação com a API.');
    error.status = response.status;
    error.payload = data;
    throw error;
  }

  return data;
}

function escapeHtml(value) {
  const text = value === null || value === undefined ? '' : String(value);
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function currentPortalFile() {
  const path = String(window.location.pathname || '').replace(/\\/g, '/').replace(/\/+$/, '');
  let file = path.split('/').pop() || '';
  try {
    file = decodeURIComponent(file);
  } catch (error) {
    // mantém o valor original se a URL estiver malformada
  }
  return file.toLowerCase();
}

function isCurrentPortalFile(name) {
  const current = currentPortalFile();
  const target = String(name || '').toLowerCase();
  if (!target) {
    return false;
  }
  if (current === target) {
    return true;
  }
  const stripExt = function (value) {
    return value.replace(/\.(html|php)$/i, '');
  };
  return stripExt(current) === stripExt(target);
}

function showPortalFeedback(message, variant) {
  const alert = document.createElement('div');
  alert.className = 'alert alert-' + (variant || 'success') + ' position-fixed top-0 end-0 m-3 shadow';
  alert.style.zIndex = '1080';
  alert.setAttribute('role', 'status');
  alert.textContent = message;
  document.body.appendChild(alert);

  setTimeout(function () {
    alert.remove();
  }, 3500);
}

window.currentPortalFile = currentPortalFile;
window.isCurrentPortalFile = isCurrentPortalFile;
window.showPortalFeedback = showPortalFeedback;
