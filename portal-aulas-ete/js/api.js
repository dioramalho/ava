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
