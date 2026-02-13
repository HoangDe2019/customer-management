import axios, { type AxiosError } from 'axios';

const baseURL = import.meta.env.VITE_API_URL || '/api';

export const api = axios.create({
  baseURL,
  headers: { 'Content-Type': 'application/json' },
});

api.interceptors.request.use((config) => {
  const token = sessionStorage.getItem('access_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (res) => res,
  async (err: AxiosError<{ message?: string; error?: string }>) => {
    if (err.response?.status === 401) {
      sessionStorage.removeItem('access_token');
      sessionStorage.removeItem('user');
      window.location.href = '/login';
    }
    return Promise.reject(err);
  }
);

export function setAuthToken(token: string) {
  sessionStorage.setItem('access_token', token);
}

export function clearAuth() {
  sessionStorage.removeItem('access_token');
  sessionStorage.removeItem('user');
}

export function getStoredUser() {
  const raw = sessionStorage.getItem('user');
  return raw ? JSON.parse(raw) : null;
}

export function storeUser(user: unknown) {
  sessionStorage.setItem('user', JSON.stringify(user));
}
