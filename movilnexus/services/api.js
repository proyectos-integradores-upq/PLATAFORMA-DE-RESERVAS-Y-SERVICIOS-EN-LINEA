// ─────────────────────────────────────────────────────
// services/api.js
// Cliente base que consume https://nexuspoint-api.onrender.com
// Todos los servicios lo importan; NUNCA uses fetch directo en screens.
// ─────────────────────────────────────────────────────

import AsyncStorage from '@react-native-async-storage/async-storage';

export const BASE_URL = 'https://nexuspoint-api.onrender.com';

// Lee el token guardado y arma los headers
async function buildHeaders(extra = {}) {
  const token = await AsyncStorage.getItem('@nexus_token');
  return {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...extra,
  };
}

// Wrapper principal — lanza Error con el detail de FastAPI
export async function apiFetch(endpoint, options = {}) {
  const headers = await buildHeaders(options.headers);

  const res = await fetch(`${BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  // 204 No Content (DELETE, algunas PUT) → devuelve null sin intentar parsear
  if (res.status === 204) return null;

  const data = await res.json().catch(() => ({}));

  if (!res.ok) {
    // FastAPI devuelve { detail: "..." }
    throw new Error(data.detail || `HTTP ${res.status}`);
  }

  return data;
}

// Helpers de conveniencia — los servicios los usan así:
//   api.get('/espacios/')
//   api.post('/auth/login', body)
//   api.put('/reservaciones/5/cancelar')
//   api.del('/notificaciones/3')
export const api = {
  get:  (url)        => apiFetch(url),
  post: (url, body)  => apiFetch(url, { method: 'POST',  body: JSON.stringify(body) }),
  put:  (url, body)  => apiFetch(url, { method: 'PUT',   body: body ? JSON.stringify(body) : undefined }),
  del:  (url)        => apiFetch(url, { method: 'DELETE' }),
};
