import AsyncStorage from '@react-native-async-storage/async-storage';

export const BASE_URL = 'https://nexuspoint-api.onrender.com';

export async function apiFetch(endpoint, options = {}) {
  const token = await AsyncStorage.getItem('token');

  const headers = {
    'Content-Type': 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...options.headers,
  };

  const response = await fetch(`${BASE_URL}${endpoint}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.detail || `Error ${response.status}`);
  }

  if (response.status === 204) return null;
  return response.json();
}