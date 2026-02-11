import { api } from '../lib/api';
import type { PaginatedResponse } from '../types';

export interface DailyAdvanceSummary {
  date: string;
  advances?: unknown[];
  total?: number;
  [key: string]: unknown;
}

export async function getDailyAdvances(agentId: number, date: string): Promise<DailyAdvanceSummary> {
  const { data } = await api.get<DailyAdvanceSummary>('/daily-advances', {
    params: { agent_id: agentId, date },
  });
  return data;
}

export async function settleDailyAdvances(
  agentId: number,
  date: string
): Promise<{ success?: boolean; message?: string }> {
  const { data } = await api.post('/daily-advances/settle', { agent_id: agentId, date });
  return data;
}

export interface EodSettlementData {
  [key: string]: unknown;
}

export async function getEodSettlement(
  agentId: number,
  date: string
): Promise<Record<string, unknown>> {
  const { data } = await api.get('/settlements/eod', {
    params: { agent_id: agentId, date },
  });
  return data;
}

export async function saveEodSettlement(
  agentId: number,
  date: string,
  settlementData: EodSettlementData
): Promise<unknown> {
  const { data } = await api.post('/settlements/eod', {
    agent_id: agentId,
    date,
    settlement_data: settlementData,
  });
  return data;
}

export interface EodSettlementRecord {
  id: number;
  agent_id: number;
  settlement_date: string;
  agent?: { id: number; name: string };
  settler?: { id: number; name: string };
  [key: string]: unknown;
}

export async function getSettlementHistory(params?: {
  agent_id?: number;
  per_page?: number;
  page?: number;
}): Promise<PaginatedResponse<EodSettlementRecord>> {
  const { data } = await api.get<PaginatedResponse<EodSettlementRecord>>('/settlements/history', {
    params,
  });
  return data;
}
