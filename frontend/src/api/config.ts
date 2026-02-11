import { api } from '../lib/api';
import type { Config } from '../types';

export async function getConfig(): Promise<Config> {
  const { data } = await api.get<Config>('/config');
  return data;
}
