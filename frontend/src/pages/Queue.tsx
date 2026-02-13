import { useEffect, useState, useCallback } from 'react';
import {
  Box,
  Card,
  CardContent,
  Typography,
  Button,
  Alert,
  CircularProgress,
  TextField,
  MenuItem,
  Chip,
  Skeleton,
  Fade,
} from '@mui/material';
import {
  type QueueStatus,
  type CloneLastRun,
} from '../api/queue';
import { subscribeJobUpdates } from '../lib/echo';
import { dispatchJob, getCloneStatus, getQueueStatus, triggerCloneToStaging } from '../api/graphqlApi';

const JOB_TYPES = [
  { value: 'sync_to_staging', label: 'Clone to Staging' },
  { value: 'export_transactions', label: 'Export Transactions' },
  { value: 'cccd_scan', label: 'CCCD Scan (async)' },
];

export function Queue() {
  const [queueStatus, setQueueStatus] = useState<QueueStatus | null>(null);
  const [cloneLast, setCloneLast] = useState<CloneLastRun | null>(null);
  const [loading, setLoading] = useState(true);
  const [cloneTriggering, setCloneTriggering] = useState(false);
  const [dispatchType, setDispatchType] = useState('sync_to_staging');
  const [dispatchPayload, setDispatchPayload] = useState('{}');
  const [dispatching, setDispatching] = useState(false);
  const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

  const fetchStatus = useCallback(async () => {
    try {
      const [status, clone] = await Promise.all([getQueueStatus(), getCloneStatus()]);
      setQueueStatus(status);
      setCloneLast(clone.last_run ?? null);
    } catch {
      setQueueStatus(null);
      setCloneLast(null);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchStatus();
    const interval = setInterval(fetchStatus, 5000);
    return () => clearInterval(interval);
  }, [fetchStatus]);

  useEffect(() => {
    const unsub = subscribeJobUpdates(() => {
      fetchStatus();
    });
    return () => unsub?.();
  }, [fetchStatus]);

  const handleTriggerClone = async () => {
    setCloneTriggering(true);
    setMessage(null);
    try {
      await triggerCloneToStaging();
      setMessage({ type: 'success', text: 'Job added to queue. Worker will process it.' });
      fetchStatus();
    } catch {
      setMessage({ type: 'error', text: 'Failed to add clone job.' });
    } finally {
      setCloneTriggering(false);
    }
  };

  const handleDispatch = async () => {
    setDispatching(true);
    setMessage(null);
    try {
      let payload: Record<string, unknown> = {};
      try {
        payload = JSON.parse(dispatchPayload || '{}');
      } catch {
        setMessage({ type: 'error', text: 'Invalid JSON payload' });
        setDispatching(false);
        return;
      }
      await dispatchJob({ type: dispatchType, payload });
      setMessage({ type: 'success', text: 'Job added to queue.' });
      fetchStatus();
    } catch {
      setMessage({ type: 'error', text: 'Failed to dispatch job.' });
    } finally {
      setDispatching(false);
    }
  };

  const formatTime = (s?: string) => (s ? new Date(s).toLocaleString() : '—');

  return (
    <Fade in>
      <Box sx={{ maxWidth: 900, mx: 'auto' }}>
        <Typography variant="h5" fontWeight={600} sx={{ mb: 2 }}>
          Queue & Jobs
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
          Add jobs to the queue; they are processed by <code>php artisan queue:work</code>. Status
          updates every 5s.
        </Typography>

        {message && (
          <Alert
            severity={message.type}
            onClose={() => setMessage(null)}
            sx={{ mb: 2 }}
          >
            {message.text}
          </Alert>
        )}

        <Box sx={{ display: 'grid', gap: 2, gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' } }}>
          <Card variant="outlined">
            <CardContent>
              <Typography variant="subtitle1" fontWeight={600} gutterBottom>
                Queue status
              </Typography>
              {loading ? (
                <Skeleton variant="rounded" height={60} />
              ) : queueStatus ? (
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, flexWrap: 'wrap' }}>
                  <Chip
                    label={`Pending: ${queueStatus.pending_count ?? '—'}`}
                    color="primary"
                    variant="outlined"
                  />
                  <Typography variant="caption" color="text.secondary">
                    {queueStatus.driver} / {queueStatus.connection}
                  </Typography>
                </Box>
              ) : (
                <Typography color="text.secondary">Could not load status.</Typography>
              )}
            </CardContent>
          </Card>

          <Card variant="outlined">
            <CardContent>
              <Typography variant="subtitle1" fontWeight={600} gutterBottom>
                Clone to Staging
              </Typography>
              {loading ? (
                <Skeleton variant="rounded" height={80} />
              ) : (
                <>
                  <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
                    Last run: {cloneLast ? (
                      <Chip
                        size="small"
                        label={cloneLast.status}
                        color={cloneLast.status === 'success' ? 'success' : cloneLast.status === 'failed' ? 'error' : 'default'}
                        sx={{ ml: 0.5 }}
                      />
                    ) : 'Never'}
                    {cloneLast?.finished_at && ` · ${formatTime(cloneLast.finished_at)}`}
                  </Typography>
                  {cloneLast?.message && (
                    <Typography variant="caption" display="block" color="text.secondary" sx={{ mb: 1 }}>
                      {cloneLast.message}
                    </Typography>
                  )}
                  <Button
                    variant="contained"
                    startIcon={cloneTriggering ? <CircularProgress size={16} color="inherit" /> : null}
                    onClick={handleTriggerClone}
                    disabled={cloneTriggering || cloneLast?.status === 'running'}
                  >
                    {cloneTriggering ? 'Adding…' : cloneLast?.status === 'running' ? 'Running…' : 'Run clone now'}
                  </Button>
                </>
              )}
            </CardContent>
          </Card>
        </Box>

        <Card variant="outlined" sx={{ mt: 2 }}>
          <CardContent>
            <Typography variant="subtitle1" fontWeight={600} gutterBottom>
              Dispatch job
            </Typography>
            <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2, maxWidth: 400 }}>
              <TextField
                select
                size="small"
                label="Job type"
                value={dispatchType}
                onChange={(e) => setDispatchType(e.target.value)}
              >
                {JOB_TYPES.map((o) => (
                  <MenuItem key={o.value} value={o.value}>{o.label}</MenuItem>
                ))}
              </TextField>
              <TextField
                size="small"
                label="Payload (JSON)"
                value={dispatchPayload}
                onChange={(e) => setDispatchPayload(e.target.value)}
                multiline
                rows={2}
                placeholder='{"key": "value"}'
              />
              <Button
                variant="outlined"
                onClick={handleDispatch}
                disabled={dispatching}
              >
                {dispatching ? 'Adding…' : 'Add to queue'}
              </Button>
            </Box>
          </CardContent>
        </Card>
      </Box>
    </Fade>
  );
}
