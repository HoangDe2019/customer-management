export interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  is_admin: boolean;
  agents?: Agent[];
}

export interface Agent {
  id: number;
  agent_id: string;
  name: string;
  status: string;
  allowed_users?: string[];
  created_date?: string;
  creator?: { id: number; name: string; email: string };
}

export interface Transaction {
  id: number;
  transaction_id: string;
  agent_id: number;
  user_id?: number;
  customer_name: string;
  cccd_number?: string;
  total_amount: number;
  transaction_type: 'Đáo' | 'Rút';
  pos_fee_percent?: number;
  agent_fee_percent?: number;
  pos_fee_amount?: number;
  agent_fee_amount?: number;
  profit?: number;
  refund_to_agent?: number;
  agent_advance?: number;
  net_settlement?: number;
  status: string;
  transaction_date: string;
  agent?: Agent;
  user?: User;
  daily_advances?: unknown[];
  logs?: TransactionLog[];
}

export interface TransactionLog {
  id: number;
  action: string;
  old_value: string | null;
  new_value: string | null;
  created_at: string;
}

export interface AuthResponse {
  user: User;
  access_token: string;
  token: string;
  token_type: string;
  expires_in: number;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface Config {
  version: string;
  default_pos_fee: number;
  default_agent_fee: number;
  currency: string;
  timezone: string;
  status_values: string[];
  amount_suggestions: number[];
}

export interface Statistics {
  total_transactions: number;
  total_amount: number;
  total_profit: number;
  total_agent_advance: number;
  dao_count: number;
  rut_count: number;
  recent: Array<{ name: string; amount: number; type: string; date: string; status: string }>;
  by_status: Record<string, number>;
}

export interface SummaryItem {
  agent_id: string;
  name: string;
  total_transactions: number;
  total_amount: number;
  total_profit: number;
}

export interface ExportResult {
  success: boolean;
  record_count: number;
  date_from: string;
  date_to: string;
  summary: {
    total_amount: number;
    total_profit: number;
    total_refund_to_agent: number;
    total_advance: number;
    net_settlement: number;
  };
  transactions: Transaction[];
}

export interface MoMoQRResponse {
  qrCodeUrl: string | null;
  payUrl: string | null;
  deeplink: string | null;
  resultCode: number | string;
  message: string;
}

export interface CCCDScanResult {
  cccdNumber?: string;
  fullName?: string;
  confidence?: number;
  error?: string;
}
