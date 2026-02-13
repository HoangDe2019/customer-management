import { useEffect, useState } from 'react';
import { Autocomplete, TextField } from '@mui/material';
import { getUserAgents } from '../api/agents';
import type { Agent } from '../types';

interface AgentSelectProps {
  value: number | '';
  onChange: (agentId: number | '') => void;
  placeholder?: string;
  required?: boolean;
  disabled?: boolean;
  size?: 'small' | 'medium';
  label?: string;
}

export function AgentSelect({
  value,
  onChange,
  placeholder = 'Chọn đại lý',
  required,
  disabled,
  size = 'small',
  label,
}: AgentSelectProps) {
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getUserAgents()
      .then(setAgents)
      .catch(() => setAgents([]))
      .finally(() => setLoading(false));
  }, []);

  const selectedAgent = value === '' || value === 0
    ? null
    : agents.find((a) => a.id === value) ?? null;

  return (
    <Autocomplete<Agent>
      value={selectedAgent}
      onChange={(_, newValue) => onChange(newValue ? newValue.id : '')}
      options={agents}
      getOptionLabel={(option) => option.name ?? ''}
      filterOptions={(options, { inputValue }) => {
        const q = (inputValue || '').trim().toLowerCase();
        if (!q) return options;
        return options.filter(
          (a) => (a.name ?? '').toLowerCase().includes(q) || String(a.id).includes(q)
        );
      }}
      loading={loading}
      disabled={disabled}
      size={size}
      renderInput={(params) => (
        <TextField
          {...params}
          label={label ?? placeholder}
          required={required}
          placeholder={placeholder}
        />
      )}
      isOptionEqualToValue={(option, v) => option.id === v?.id}
    />
  );
}
