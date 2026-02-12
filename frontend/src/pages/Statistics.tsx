import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import {
  Box,
  Card,
  CardContent,
  Chip,
  Container,
  Fade,
  MenuItem,
  Skeleton,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  TextField,
  Typography,
} from '@mui/material';
import { getStatistics } from '../api/statistics';
import { AgentSelect } from '../components/AgentSelect';
import type { Statistics as StatsType } from '../types';

function formatMoney(n: number) {
  return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n) + ' ₫';
}

export function Statistics() {
  const [searchParams] = useSearchParams();
  const agentIdParam = searchParams.get('agent_id');
  const [agentId, setAgentId] = useState<number | ''>(() => {
    if (agentIdParam) {
      const n = parseInt(agentIdParam, 10);
      return isNaN(n) ? '' : n;
    }
    return '';
  });
  const [period, setPeriod] = useState<'all' | 'today' | '3days' | 'week' | 'month'>('all');
  const [data, setData] = useState<StatsType | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!agentId) {
      setData(null);
      return;
    }
    let cancelled = false;
    setLoading(true);
    getStatistics(agentId, period)
      .then((res) => { if (!cancelled) setData(res); })
      .catch(() => { if (!cancelled) setData(null); })
      .finally(() => { if (!cancelled) setLoading(false); });
    return () => { cancelled = true; };
  }, [agentId, period]);

  return (
    <Fade in>
      <Container maxWidth="lg">
        <Box sx={{ mb: 3 }}>
          <Typography variant="h5" sx={{ mb: 1 }}>
            Thống kê (chỉ giao dịch Hoàn thành)
          </Typography>
          <Typography variant="body2" color="text.secondary">
            Chọn đại lý và khoảng thời gian để xem tổng hợp giao dịch, lợi nhuận và lịch sử gần đây.
          </Typography>
        </Box>

        <Card sx={{ mb: 3 }} variant="outlined">
          <CardContent>
            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2 }}>
              <Box sx={{ minWidth: 220 }}>
                <AgentSelect
                  value={agentId}
                  onChange={setAgentId}
                  placeholder="Chọn đại lý"
                  label="Đại lý"
                />
              </Box>

              <TextField
                select
                size="small"
                label="Khoảng thời gian"
                value={period}
                onChange={(e) => setPeriod(e.target.value as 'all' | 'today' | '3days' | 'week' | 'month')}
                sx={{ minWidth: 180 }}
              >
                <MenuItem value="all">Tất cả</MenuItem>
                <MenuItem value="today">Hôm nay</MenuItem>
                <MenuItem value="3days">3 ngày</MenuItem>
                <MenuItem value="week">Tuần</MenuItem>
                <MenuItem value="month">Tháng</MenuItem>
              </TextField>
            </Box>
          </CardContent>
        </Card>

        {loading && (
          <Box sx={{ maxWidth: 800 }}>
            <Skeleton variant="text" width={200} height={32} sx={{ mb: 1 }} />
            <Skeleton variant="rounded" height={120} sx={{ mb: 2 }} />
            <Skeleton variant="rounded" height={220} />
          </Box>
        )}

        {!loading && data && (
          <>
            <Card sx={{ mb: 3 }} variant="outlined">
              <CardContent>
                <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                  Tổng quan
                </Typography>
                <Box
                  sx={{
                    display: 'grid',
                    gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', md: 'repeat(3, 1fr)' },
                    gap: 2,
                  }}
                >
                  <Box>
                    <Typography variant="body2" color="text.secondary">
                      Tổng giao dịch
                    </Typography>
                    <Typography variant="h6">{data.total_transactions}</Typography>
                  </Box>
                  <Box>
                    <Typography variant="body2" color="text.secondary">
                      Tổng tiền
                    </Typography>
                    <Typography variant="h6">{formatMoney(data.total_amount)}</Typography>
                  </Box>
                  <Box>
                    <Typography variant="body2" color="text.secondary">
                      Lợi nhuận
                    </Typography>
                    <Typography variant="h6">{formatMoney(data.total_profit)}</Typography>
                  </Box>
                  <Box>
                    <Typography variant="body2" color="text.secondary">
                      Tạm ứng đại lý
                    </Typography>
                    <Typography variant="h6">{formatMoney(data.total_agent_advance)}</Typography>
                  </Box>
                  <Box>
                    <Typography variant="body2" color="text.secondary">
                      Đáo
                    </Typography>
                    <Typography variant="h6">{data.dao_count}</Typography>
                  </Box>
                  <Box>
                    <Typography variant="body2" color="text.secondary">
                      Rút
                    </Typography>
                    <Typography variant="h6">{data.rut_count}</Typography>
                  </Box>
                </Box>

                {data.by_status && Object.keys(data.by_status).length > 0 && (
                  <Box sx={{ mt: 2, display: 'flex', flexWrap: 'wrap', gap: 1 }}>
                    {Object.entries(data.by_status).map(([status, cnt]) => (
                      <Chip key={status} label={`${status}: ${cnt}`} variant="outlined" size="small" />
                    ))}
                  </Box>
                )}
              </CardContent>
            </Card>

            {data.recent && data.recent.length > 0 && (
              <Card variant="outlined">
                <CardContent>
                  <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                    Giao dịch gần đây
                  </Typography>
                  <Table size="small">
                    <TableHead>
                      <TableRow>
                        <TableCell>Khách</TableCell>
                        <TableCell>Số tiền</TableCell>
                        <TableCell>Loại</TableCell>
                        <TableCell>Ngày</TableCell>
                        <TableCell>Trạng thái</TableCell>
                      </TableRow>
                    </TableHead>
                    <TableBody>
                      {data.recent.map((r, i) => (
                        <TableRow key={i}>
                          <TableCell>{r.name}</TableCell>
                          <TableCell>{formatMoney(r.amount)}</TableCell>
                          <TableCell>{r.type}</TableCell>
                          <TableCell>{r.date}</TableCell>
                          <TableCell>{r.status}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
              </Card>
            )}
          </>
        )}

        {!loading && !data && (
          <Typography color="text.secondary">Chọn đại lý để xem thống kê.</Typography>
        )}
      </Container>
    </Fade>
  );
}
