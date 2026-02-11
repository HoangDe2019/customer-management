import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
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
  TextField,
  MenuItem,
  Chip,
  Fade,
  CircularProgress,
} from '@mui/material';
import { getTransactions, type TransactionFilters } from '../api/transactions';
import { AgentSelect } from '../components/AgentSelect';
import type { Transaction, PaginatedResponse } from '../types';

const STATUS_OPTIONS = [
  'Chờ duyệt',
  'Đã duyệt',
  'Đang xử lý',
  'Chờ DR',
  'Hoàn thành',
  'Thất bại',
  'Đã hủy',
];

function formatMoney(n: number) {
  return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n) + ' ₫';
}

function formatDate(s: string) {
  if (!s) return '-';
  const d = new Date(s);
  return d.toLocaleDateString('vi-VN') + ' ' + d.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });
}

export function Transactions() {
  const [data, setData] = useState<PaginatedResponse<Transaction> | null>(null);
  const [agentId, setAgentId] = useState<number | ''>('');
  const [status, setStatus] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [type, setType] = useState('');
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    const params: Record<string, unknown> = { page, per_page: 15 };
    if (agentId) params.agent_id = agentId;
    if (status) params.status = status;
    if (dateFrom) params.date_from = dateFrom;
    if (dateTo) params.date_to = dateTo;
    if (type) params.transaction_type = type;
    getTransactions(params as TransactionFilters)
      .then(setData)
      .finally(() => setLoading(false));
  }, [page, agentId, status, dateFrom, dateTo, type]);

  const transactions = data?.data ?? [];
  const total = data?.total ?? 0;
  const lastPage = data?.last_page ?? 1;

  return (
    <Fade in>
      <Box sx={{ maxWidth: 1200, mx: 'auto' }}>
        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 2, mb: 2 }}>
          <Typography variant="h5" fontWeight={600}>Giao dịch</Typography>
          <Button component={Link} to="/transactions/new" variant="contained">Tạo giao dịch</Button>
        </Box>

        <Paper variant="outlined" sx={{ p: 2, mb: 2 }}>
          <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2, alignItems: 'center' }}>
            <Box sx={{ minWidth: 180 }}>
              <AgentSelect value={agentId} onChange={(id) => { setAgentId(id); setPage(1); }} />
            </Box>
            <TextField select size="small" label="Trạng thái" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }} sx={{ minWidth: 160 }}>
              <MenuItem value="">Tất cả</MenuItem>
              {STATUS_OPTIONS.map((s) => <MenuItem key={s} value={s}>{s}</MenuItem>)}
            </TextField>
            <TextField select size="small" label="Loại" value={type} onChange={(e) => { setType(e.target.value); setPage(1); }} sx={{ minWidth: 120 }}>
              <MenuItem value="">Tất cả</MenuItem>
              <MenuItem value="Đáo">Đáo</MenuItem>
              <MenuItem value="Rút">Rút</MenuItem>
            </TextField>
            <TextField size="small" type="date" label="Từ ngày" value={dateFrom} onChange={(e) => { setDateFrom(e.target.value); setPage(1); }} InputLabelProps={{ shrink: true }} />
            <TextField size="small" type="date" label="Đến ngày" value={dateTo} onChange={(e) => { setDateTo(e.target.value); setPage(1); }} InputLabelProps={{ shrink: true }} />
            <Button variant="outlined" onClick={() => setPage(1)}>Làm mới</Button>
          </Box>
        </Paper>

        {loading && (
          <Box sx={{ display: 'flex', justifyContent: 'center', py: 4 }}>
            <CircularProgress />
          </Box>
        )}
        {!loading && (
          <>
            <Paper variant="outlined" sx={{ overflow: 'hidden' }}>
              <Table size="small">
                <TableHead>
                  <TableRow>
                    <TableCell><strong>Mã GD</strong></TableCell>
                    <TableCell><strong>Khách hàng</strong></TableCell>
                    <TableCell><strong>Đại lý</strong></TableCell>
                    <TableCell><strong>Loại</strong></TableCell>
                    <TableCell><strong>Số tiền</strong></TableCell>
                    <TableCell><strong>Trạng thái</strong></TableCell>
                    <TableCell><strong>Ngày</strong></TableCell>
                    <TableCell></TableCell>
                  </TableRow>
                </TableHead>
                <TableBody>
                  {transactions.map((t) => (
                    <TableRow key={t.id} hover>
                      <TableCell>{t.transaction_id}</TableCell>
                      <TableCell>{t.customer_name}</TableCell>
                      <TableCell>{t.agent?.name ?? '-'}</TableCell>
                      <TableCell>{t.transaction_type}</TableCell>
                      <TableCell>{formatMoney(Number(t.total_amount))}</TableCell>
                      <TableCell><Chip size="small" label={t.status} variant="outlined" /></TableCell>
                      <TableCell>{formatDate(t.transaction_date)}</TableCell>
                      <TableCell><Button size="small" component={Link} to={`/transactions/${t.id}`}>Chi tiết</Button></TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </Paper>
            <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, mt: 2 }}>
              <Button disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>Trước</Button>
              <Typography variant="body2">Trang {page} / {lastPage} (tổng {total})</Typography>
              <Button disabled={page >= lastPage} onClick={() => setPage((p) => p + 1)}>Sau</Button>
            </Box>
          </>
        )}
      </Box>
    </Fade>
  );
}
