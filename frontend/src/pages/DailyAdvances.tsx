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
import { getDailyAdvances, settleDailyAdvances } from '../api/settlements';
import { AgentSelect } from '../components/AgentSelect';

function toDDMMYYYY(d: Date) {
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

export function DailyAdvances() {
  const [agentId, setAgentId] = useState<number | ''>('');
  const [date, setDate] = useState(toDDMMYYYY(new Date()));
  const [data, setData] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(false);
  const [settling, setSettling] = useState(false);

  const load = () => {
    if (!agentId) return;
    setLoading(true);
    getDailyAdvances(agentId, date)
      .then(setData)
      .finally(() => setLoading(false));
  };

  const handleSettle = async () => {
    if (!agentId) return;
    setSettling(true);
    try {
      await settleDailyAdvances(agentId, date);
      load();
    } finally {
      setSettling(false);
    }
  };

  return (
    <Container maxWidth="md">
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" sx={{ mb: 1 }}>
          Tạm ứng theo ngày
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Xem và đối soát tạm ứng theo ngày cho từng đại lý.
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
              {loading ? 'Đang tải...' : 'Xem'}
            </Button>
          </Box>
        </CardContent>
      </Card>

      {data && (
        <Card variant="outlined">
          <CardContent>
            <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
              Kết quả tạm ứng
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
                onClick={handleSettle}
                disabled={settling}
                startIcon={settling ? <CircularProgress size={16} color="inherit" /> : null}
              >
                {settling ? 'Đang đối soát...' : 'Đối soát tạm ứng'}
              </Button>
            </Box>
          </CardContent>
        </Card>
      )}
    </Container>
  );
}
