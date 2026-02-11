import { useState } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  Container,
  TextField,
  Typography,
  CircularProgress,
} from '@mui/material';
import { getEodSettlement, saveEodSettlement } from '../api/settlements';
import { AgentSelect } from '../components/AgentSelect';

function toDDMMYYYY(d: Date) {
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

export function SettlementEod() {
  const [agentId, setAgentId] = useState<number | ''>('');
  const [date, setDate] = useState(toDDMMYYYY(new Date()));
  const [data, setData] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  const load = () => {
    if (!agentId) return;
    setLoading(true);
    getEodSettlement(agentId, date)
      .then(setData)
      .finally(() => setLoading(false));
  };

  const handleSave = async () => {
    if (!agentId || !data) return;
    setSaving(true);
    try {
      await saveEodSettlement(agentId, date, data as Record<string, unknown>);
      load();
    } finally {
      setSaving(false);
    }
  };

  return (
    <Container maxWidth="md">
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" sx={{ mb: 1 }}>
          Đối soát cuối ngày
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Tính và lưu kết quả đối soát cuối ngày theo đại lý và ngày.
        </Typography>
      </Box>

      <Card sx={{ mb: 3 }} variant="outlined">
        <CardContent>
          <Box
            sx={{
              display: 'flex',
              flexWrap: 'wrap',
              gap: 2,
              alignItems: 'center',
            }}
          >
            <Box sx={{ minWidth: 220 }}>
              <AgentSelect value={agentId} onChange={setAgentId} />
            </Box>
            <TextField
              label="Ngày (dd/mm/yyyy)"
              size="small"
              value={date}
              onChange={(e) => setDate(e.target.value)}
              placeholder="01/01/2025"
            />
            <Button
              variant="contained"
              onClick={load}
              disabled={!agentId || loading}
              startIcon={loading ? <CircularProgress size={16} color="inherit" /> : null}
            >
              {loading ? 'Đang tải...' : 'Tính đối soát'}
            </Button>
          </Box>
        </CardContent>
      </Card>

      {data && (
        <Card variant="outlined">
          <CardContent>
            <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
              Kết quả đối soát
            </Typography>
            <Box
              component="pre"
              sx={{
                bgcolor: 'grey.100',
                borderRadius: 1,
                p: 2,
                maxHeight: 320,
                overflow: 'auto',
                fontSize: 13,
              }}
            >
              {JSON.stringify(data, null, 2)}
            </Box>
            <Box sx={{ mt: 2, textAlign: 'right' }}>
              <Button
                variant="contained"
                onClick={handleSave}
                disabled={saving}
                startIcon={saving ? <CircularProgress size={16} color="inherit" /> : null}
              >
                {saving ? 'Đang lưu...' : 'Lưu đối soát cuối ngày'}
              </Button>
            </Box>
          </CardContent>
        </Card>
      )}
    </Container>
  );
}
