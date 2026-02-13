import { api } from "../lib/api";
import type { Agent, Config, ExportResult, MoMoQRResponse, PaginatedResponse, Statistics, SummaryItem, Transaction } from "../types";

export interface TransactionFilters {
    agent_id?: number;
    status?: string;
    date_from?: string;
    date_to?: string;
    transaction_type?: string;
    per_page?: number;
    page?: number;
}

export async function generateMoMoQR(payload: {
    customer_name: string;
    total_amount: number;
    transaction_type: 'Đáo' | 'Rút';
}): Promise<MoMoQRResponse> {
    const { data } = await api.post<MoMoQRResponse>('/momo/generate-qr', payload);
    return data;
}

export async function getConfig(): Promise<Config> {
    const { data } = await api.get<Config>('/config');
    return data;
}

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

export interface AgentsResponse {
    agents: Agent[];
}

export async function getAgents(): Promise<Agent[]> {
    const { data } = await api.get<AgentsResponse>('/agents');
    return data.agents;
}

export async function getUserAgents(): Promise<Agent[]> {
    const { data } = await api.get<Agent[]>('/agents/user-agents');
    return Array.isArray(data) ? data : [];
}

export async function getAgent(id: number): Promise<Agent> {
    const { data } = await api.get<Agent>(`/agents/${id}`);
    return data;
}

export async function createAgent(payload: { name: string; allowed_users?: string }): Promise<Agent> {
    const { data } = await api.post<{ agent: Agent }>('/agents', payload);
    return data.agent;
}

export async function updateAgent(
    id: number,
    payload: { status?: string; allowed_users?: string }
): Promise<Agent> {
    const { data } = await api.put<{ agent: Agent }>(`/agents/${id}`, payload);
    return data.agent;
}

export async function deleteAgent(id: number): Promise<void> {
    await api.delete(`/agents/${id}`);
}

export async function getTransactions(
    params?: TransactionFilters
): Promise<PaginatedResponse<Transaction>> {
    const { data } = await api.get<PaginatedResponse<Transaction>>('/transactions', { params });
    return data;
}

  export async function getTransaction(id: number): Promise<Transaction> {
    const { data } = await api.get<Transaction>(`/transactions/${id}`);
    return data;
  }

  export interface CreateTransactionPayload {
    agent_id: number;
    customer_name?: string;
    cccd_number?: string;
    total_amount: number;
    transaction_type: 'Đáo' | 'Rút';
    pos_fee_percent?: number;
    agent_fee_percent?: number;
    agent_advance?: number;
  }

  export async function createTransaction(payload: CreateTransactionPayload): Promise<Transaction> {
    const { data } = await api.post<Transaction>('/transactions', payload);
    return data;
  }

  export async function updateTransactionStatus(
    id: number,
    status: string
  ): Promise<Transaction> {
    const { data } = await api.put<Transaction>(`/transactions/${id}/status`, { status });
    return data;
  }

  export interface ExportParams {
    agent_id: number;
    date_from: string; // dd/mm/yyyy
    date_to: string;
    status?: string;
    type?: string;
  }

  export async function exportTransactions(params: ExportParams): Promise<ExportResult> {
    const { data } = await api.get<ExportResult>('/transactions/export', { params });
    return data;
  }

  export async function scanCCCD(imageBase64: string): Promise<{
    cccdNumber?: string;
    fullName?: string;
    confidence?: number;
    error?: string;
  }> {
    const { data } = await api.post('/cccd/scan', { image: imageBase64 });
    return data;
  }
