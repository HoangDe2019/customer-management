export {
  getDailyAdvances,
  settleDailyAdvances,
  getEodSettlement,
  saveEodSettlement,
  getSettlementHistory,
} from './graphqlApi';

export interface DailyAdvanceSummary {
  date: string;
  advances?: unknown[];
  total?: number;
  [key: string]: unknown;
}

export interface EodSettlementData {
  [key: string]: unknown;
}

export interface EodSettlementRecord {
  id: number;
  agent_id: number;
  settlement_date: string;
  agent?: { id: number; name: string };
  settler?: { id: number; name: string };
  [key: string]: unknown;
}

export type { PaginatedResponse } from '../types';
