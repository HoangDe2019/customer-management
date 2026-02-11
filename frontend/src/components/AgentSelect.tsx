import { useEffect, useState } from 'react';
import { getUserAgents } from '../api/agents';
import type { Agent } from '../types';

interface AgentSelectProps {
  value: number | '';
  onChange: (agentId: number) => void;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
}

export function AgentSelect({
  value,
  onChange,
  placeholder = 'Chọn đại lý',
  required,
  disabled,
}: AgentSelectProps) {
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getUserAgents()
      .then(setAgents)
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return <select disabled><option>Đang tải...</option></select>;
  }

  return (
    <select
      value={value === '' ? '' : value}
      onChange={(e) => onChange(e.target.value ? Number(e.target.value) : 0)}
      required={required}
      disabled={disabled}
    >
      <option value="">{placeholder}</option>
      {agents.map((a) => (
        <option key={a.id} value={a.id}>
          {a.name}
        </option>
      ))}
    </select>
  );
}
