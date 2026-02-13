import { api, setAuthToken, storeUser, clearAuth } from '../lib/api';
import type { AuthResponse, User } from '../types';
import { graphqlRequest } from '../lib/graphql';
import * as ops from '../graphql/operations';

export async function login(email: string, password: string): Promise<AuthResponse> {
  const { data } = await api.post<AuthResponse>('/auth/login', { email, password });
  setAuthToken(data.access_token || data.token);
  storeUser(data.user);
  return data;
}

export async function register(payload: {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
}): Promise<AuthResponse> {
  const { data } = await api.post<AuthResponse>('/auth/register', payload);
  setAuthToken(data.access_token || data.token);
  storeUser(data.user);
  return data;
}

export async function logout(): Promise<void> {
  try {
    await api.post('/auth/logout');
  } finally {
    clearAuth();
  }
}

export async function refreshToken(): Promise<AuthResponse> {
  const { data } = await api.post<AuthResponse>('/auth/refresh');
  setAuthToken(data.access_token || data.token);
  storeUser(data.user);
  return data;
}

/** Lấy user hiện tại qua GraphQL. */
export async function getMe(): Promise<User> {
  const res = await graphqlRequest<{ me: User }>(ops.QUERY_ME);
  if (res.me) storeUser(res.me);
  return res.me;
}
