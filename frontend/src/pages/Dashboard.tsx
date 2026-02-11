import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import {
  Box,
  Typography,
  Card,
  CardContent,
  Skeleton,
  Fade,
  Chip,
} from '@mui/material';
import { getSummary } from '../api/statistics';
import { getConfig } from '../api/config';
import type { SummaryItem, Config } from '../types';

function formatMoney(n: number) {
  return new Intl.NumberFormat('vi-VN', { style: 'decimal', maximumFractionDigits: 0 }).format(n) + ' ₫';
}

export function Dashboard() {
  const [summary, setSummary] = useState<SummaryItem[]>([]);
  const [config, setConfig] = useState<Config | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([getSummary(), getConfig()])
      .then(([s, c]) => {
        setSummary(s);
        setConfig(c);
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  if (loading) {
    return (
      <Box sx={{ maxWidth: 1200, mx: 'auto' }}>
        <Skeleton variant="text" width={200} height={40} sx={{ mb: 2 }} />
        <Box sx={{ display: 'flex', gap: 1, mb: 2 }}>
          <Skeleton variant="rounded" width={120} height={28} />
          <Skeleton variant="rounded" width={80} height={28} />
        </Box>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', md: 'repeat(3, 1fr)' }, gap: 2 }}>
          {[1, 2, 3].map((i) => (
            <Skeleton key={i} variant="rounded" height={140} />
          ))}
        </Box>
      </Box>
    );
  }

  return (
    <Fade in>
      <Box sx={{ maxWidth: 1200, mx: 'auto' }}>
        <Typography variant="h5" fontWeight={600} sx={{ mb: 2 }}>
          Tổng quan
        </Typography>
        {config && (
          <Box sx={{ display: 'flex', gap: 1, mb: 2, flexWrap: 'wrap' }}>
            <Chip size="small" label={`v${config.version}`} variant="outlined" />
            <Chip size="small" label={config.currency} variant="outlined" />
          </Box>
        )}
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', md: 'repeat(3, 1fr)' }, gap: 2 }}>
          {summary.map((item) => (
            <Card
              key={item.agent_id}
              component={Link}
              to={`/statistics?agent_id=${item.agent_id}`}
              variant="outlined"
              sx={{
                textDecoration: 'none',
                color: 'inherit',
                transition: 'box-shadow 0.3s ease, transform 0.2s ease',
                '&:hover': {
                  boxShadow: 4,
                  transform: 'translateY(-2px)',
                },
              }}
            >
              <CardContent>
                <Typography variant="subtitle1" fontWeight={600} gutterBottom>
                  {item.name}
                </Typography>
                <Typography variant="h6" color="primary" sx={{ mb: 0.5 }}>
                  {formatMoney(item.total_amount)}
                </Typography>
                <Typography variant="body2" color="text.secondary">
                  {item.total_transactions} giao dịch · Lợi nhuận: {formatMoney(item.total_profit)}
                </Typography>
              </CardContent>
            </Card>
          ))}
        </Box>
        {summary.length === 0 && (
          <Typography color="text.secondary" sx={{ mt: 2 }}>
            Chưa có dữ liệu thống kê. Chọn đại lý để xem thống kê.
          </Typography>
        )}
      </Box>
    </Fade>
  );
}
