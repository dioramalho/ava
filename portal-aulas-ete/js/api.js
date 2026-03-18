const API_BASE_URL = 'backend/public/index.php?route=';

async function apiRequest(route, options) {
  const requestOptions = options || {};
  const finalOptions = {
    method: requestOptions.method || 'GET',
    credentials: 'same-origin',
    headers: requestOptions.headers || {}
  };

  if (requestOptions.body) {
    finalOptions.body = requestOptions.body;
  }

  const response = await fetch(API_BASE_URL + route, finalOptions);
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
