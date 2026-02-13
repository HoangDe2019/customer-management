export {
  generateMoMoQR,
} from './graphqlApi';

export interface QueueStatus {
  connection: string;
  driver: string;
  pending_count: number | null;
  message?: string;
}

export interface CloneLastRun {
  status?: 'running' | 'success' | 'failed';
  message?: string;
  started_at?: string;
  finished_at?: string;
  exit_code?: number;
}
