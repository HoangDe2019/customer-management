import { api } from '../lib/api';

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

export async function getQueueStatus(): Promise<QueueStatus> {
  const { data } = await api.get<QueueStatus>('/queue/status');
  return data;
}

export async function dispatchJob(payload: {
  type: string;
  payload?: Record<string, unknown>;
  queue?: string;
}): Promise<{ success: boolean; message: string }> {
  const { data } = await api.post<{ success: boolean; message: string }>('/queue/dispatch', payload);
  return data;
}

export async function triggerCloneToStaging(): Promise<{ success: boolean; message: string }> {
  const { data } = await api.post<{ success: boolean; message: string }>('/queue/clone-trigger');
  return data;
}

export async function getCloneStatus(): Promise<{ last_run: CloneLastRun | null }> {
  const { data } = await api.get<{ last_run: CloneLastRun | null }>('/queue/clone-status');
  return data;
}
