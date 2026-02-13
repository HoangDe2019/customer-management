import { useState, useEffect } from 'react';
import {
  Dialog,
  DialogTitle,
  DialogContent,
  DialogActions,
  TextField,
  Button,
  Alert,
  MenuItem,
} from '@mui/material';
import { createAgent, updateAgent } from '../api/agents';
import type { Agent } from '../types';

interface AgentFormProps {
  agent?: Agent | null;
  onSuccess: () => void;
  onCancel: () => void;
}

export function AgentForm({ agent, onSuccess, onCancel }: AgentFormProps) {
  const [name, setName] = useState(agent?.name ?? '');
  const [allowedUsers, setAllowedUsers] = useState(
    Array.isArray(agent?.allowed_users) ? agent?.allowed_users.join(', ') : ''
  );
  const [status, setStatus] = useState(agent?.status ?? 'Active');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const isEdit = !!agent?.id;

  useEffect(() => {
    if (agent) {
      setName(agent.name);
      setAllowedUsers(Array.isArray(agent.allowed_users) ? agent.allowed_users.join(', ') : '');
      setStatus(agent.status ?? 'Active');
    }
  }, [agent]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      if (isEdit) {
        await updateAgent(agent!.id, { status, allowed_users: allowedUsers || undefined });
      } else {
        await createAgent({ name: name.trim(), allowed_users: allowedUsers || undefined });
      }
      onSuccess();
    } catch (err: unknown) {
      const axiosErr = err as { response?: { data?: { error?: string; message?: string } } };
      const serverMsg = axiosErr.response?.data?.error || axiosErr.response?.data?.message;
      const fallbackMsg = err instanceof Error ? err.message : 'Có lỗi xảy ra';
      setError(String(serverMsg || fallbackMsg));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open onClose={onCancel} maxWidth="sm" fullWidth>
      <DialogTitle>{isEdit ? 'Cập nhật đại lý' : 'Thêm đại lý'}</DialogTitle>
      <form onSubmit={handleSubmit}>
        <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
          {error && <Alert severity="error" onClose={() => setError('')}>{error}</Alert>}
          <TextField label="Tên đại lý" value={name} onChange={(e) => setName(e.target.value)} required disabled={isEdit} fullWidth />
          {isEdit && (
            <TextField select label="Trạng thái" value={status} onChange={(e) => setStatus(e.target.value)} fullWidth>
              <MenuItem value="Active">Active</MenuItem>
              <MenuItem value="Inactive">Inactive</MenuItem>
              <MenuItem value="Deleted">Deleted</MenuItem>
            </TextField>
          )}
          <TextField label="Email người dùng (phân cách bằng dấu phẩy)" value={allowedUsers} onChange={(e) => setAllowedUsers(e.target.value)} placeholder="user1@example.com, user2@example.com" fullWidth />
        </DialogContent>
        <DialogActions>
          <Button onClick={onCancel}>Hủy</Button>
          <Button type="submit" variant="contained" disabled={loading}>
            {loading ? 'Đang lưu...' : isEdit ? 'Cập nhật' : 'Tạo'}
          </Button>
        </DialogActions>
      </form>
    </Dialog>
  );
}
