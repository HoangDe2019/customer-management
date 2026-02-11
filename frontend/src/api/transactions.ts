import { api } from '../lib/api';
import type { Transaction, PaginatedResponse, ExportResult } from '../types';

export interface TransactionFilters {
  agent_id?: number;
  status?: string;
  date_from?: string;
  date_to?: string;
  transaction_type?: string;
  per_page?: number;
  page?: number;
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
