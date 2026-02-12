import { useEffect, useState } from 'react';
import {
  Box,
  Typography,
  Button,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Chip,
  Skeleton,
  Fade,
} from '@mui/material';
import { getAgents } from '../api/agents';
import { useAuth } from '../context/AuthContext';
import { subscribeDataUpdates } from '../lib/echo';
import type { Agent } from '../types';
import { AgentForm } from '../components/AgentForm';

export function Agents() {
  const { user } = useAuth();
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [editing, setEditing] = useState<Agent | null>(null);
  const [showForm, setShowForm] = useState(false);

  const load = async () => {
    setLoading(true);
    setError(null);
    try {
      const list = await getAgents();
      setAgents(list);
    } catch {
      setError('Không tải được danh sách đại lý.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    load();
  }, []);

  useEffect(() => {
    const unsub = subscribeDataUpdates((payload) => {
      if (payload.entity === 'agents') load();
    });
    return () => unsub?.();
  }, []);

  const isAdmin = user?.is_admin ?? false;

  const handleCreated = () => {
    setShowForm(false);
    load();
  };

  const handleUpdated = () => {
    setEditing(null);
    load();
  };

  if (loading) {
    return (
      <Box sx={{ maxWidth: 1000, mx: 'auto' }}>
        <Skeleton variant="text" width={200} height={40} sx={{ mb: 2 }} />
        <Skeleton variant="rounded" height={320} />
      </Box>
    );
  }

  return (
    <Fade in>
      <Box sx={{ maxWidth: 1000, mx: 'auto' }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2, mb: 2 }}>
          <Typography variant="h5" fontWeight={600}>Đại lý</Typography>
          {isAdmin && (
            <Button variant="contained" onClick={() => { setEditing(null); setShowForm(true); }}>
              Thêm đại lý
            </Button>
          )}
        </Box>

        {error && (
          <Box sx={{ mb: 2, p: 1.5, bgcolor: 'error.light', color: 'error.contrastText', borderRadius: 1 }}>
            {error}
          </Box>
        )}
        {showForm && (
          <AgentForm onSuccess={handleCreated} onCancel={() => setShowForm(false)} />
        )}
        {editing && (
          <AgentForm agent={editing} onSuccess={handleUpdated} onCancel={() => setEditing(null)} />
        )}

        <Paper variant="outlined" sx={{ overflow: 'hidden' }}>
          <Table size="small">
            <TableHead>
              <TableRow>
                <TableCell><strong>Mã / Tên</strong></TableCell>
                <TableCell><strong>Trạng thái</strong></TableCell>
                {isAdmin && <TableCell><strong>Thao tác</strong></TableCell>}
              </TableRow>
            </TableHead>
            <TableBody>
              {agents.map((a) => (
                <TableRow key={a.id} hover>
                  <TableCell>{a.name}</TableCell>
                  <TableCell>
                    <Chip size="small" label={a.status} color={a.status === 'Active' ? 'success' : a.status === 'Deleted' ? 'error' : 'default'} variant="outlined" />
                  </TableCell>
                  {isAdmin && (
                    <TableCell>
                      {a.status !== 'Deleted' && (
                        <Box sx={{ display: 'flex', gap: 1 }}>
                          <Button size="small" onClick={() => { setShowForm(false); setEditing(a); }}>Sửa</Button>
                          {a.agent_id !== 'Khách hàng' && (
                            <Button size="small" color="error" onClick={async () => {
                              if (window.confirm('Xóa đại lý này?')) {
                                const { deleteAgent } = await import('../api/agents');
                                await deleteAgent(a.id);
                                load();
                              }
                            }}>
                              Xóa
                            </Button>
                          )}
                        </Box>
                      )}
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </Paper>
      </Box>
    </Fade>
  );
}
