export {
  getTransactions,
  getTransaction,
  createTransaction,
  updateTransactionStatus,
  scanCCCD,
} from './graphqlApi';
export type { TransactionFilters } from './graphqlApi';
export type { Transaction, PaginatedResponse, ExportResult } from '../types';

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

import { api } from '../lib/api';
import type { ExportResult } from '../types';

export interface ExportParams {
  agent_id: number;
  date_from: string;
  date_to: string;
  status?: string;
  type?: string;
}

/** Export transactions vẫn dùng REST (chưa có GraphQL). */
export async function exportTransactions(params: ExportParams): Promise<ExportResult> {
  const { data } = await api.get<ExportResult>('/transactions/export', { params });
  return data;
}
