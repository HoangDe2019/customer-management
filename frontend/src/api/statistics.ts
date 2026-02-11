import { api } from '../lib/api';
import type { Statistics, SummaryItem } from '../types';

export async function getStatistics(
  agentId: number,
  period?: 'all' | 'today' | '3days' | 'week' | 'month'
): Promise<Statistics> {
  const { data } = await api.get<Statistics>('/statistics', {
    params: { agent_id: agentId, period: period || 'all' },
  });
  return data;
}

export async function getSummary(): Promise<SummaryItem[]> {
  const { data } = await api.get<SummaryItem[]>('/statistics/summary');
  return Array.isArray(data) ? data : [];
}
