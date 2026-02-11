import { api } from '../lib/api';
import type { Agent } from '../types';

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
