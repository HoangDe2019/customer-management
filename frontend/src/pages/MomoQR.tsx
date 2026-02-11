import React, { useEffect, useRef, useState } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  Chip,
  Container,
  Grid,
  Snackbar,
  Alert,
  TextField,
  Typography,
  MenuItem,
  Paper,
} from '@mui/material';
import { generateMoMoQR } from '../api/momo';
import type { MoMoQRResponse } from '../types';

export function MomoQR() {
  const [customerName, setCustomerName] = useState('');
  const [totalAmount, setTotalAmount] = useState('');
  const [transactionType, setTransactionType] = useState<'Đáo' | 'Rút'>('Đáo');
  const [result, setResult] = useState<MoMoQRResponse | null>(null);
  const [loading, setLoading] = useState(false);
  const [paymentStatus, setPaymentStatus] = useState<'pending' | 'checking' | 'success' | 'failed'>('pending');
  const [snackbar, setSnackbar] = useState<{ open: boolean; message: string; severity: 'success' | 'error' | 'info' }>({
    open: false,
    message: '',
    severity: 'info',
  });
  const iframeRef = useRef<HTMLIFrameElement | null>(null);
  const statusCheckInterval = useRef<ReturnType<typeof setInterval> | null>(null);

  // Auto-check payment status every 3 seconds
  useEffect(() => {
    if (result && result.orderId) {
      startStatusCheck();
    }

    return () => {
      if (statusCheckInterval.current) {
        clearInterval(statusCheckInterval.current);
      }
    };
  }, [result]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    const amount = parseFloat(totalAmount);
    if (isNaN(amount) || amount < 1000) {
      setResult({
        paymentId: null,
        orderId: null,
        qrCodeUrl: null,
        payUrl: null,
        deeplink: null,
        status: 'failed',
        resultCode: -1,
        message: 'Số tiền tối thiểu 1.000 VNĐ',
      });
      setSnackbar({
        open: true,
        message: 'Số tiền tối thiểu 1.000 VNĐ',
        severity: 'error',
      });
      return;
    }
    setLoading(true);
    setResult(null);
    try {
      const res = await generateMoMoQR({
        customer_name: customerName,
        total_amount: amount,
        transaction_type: transactionType,
      });
      setResult(res);
      setPaymentStatus('pending');
    } catch {
      setResult({
        paymentId: null,
        orderId: null,
        qrCodeUrl: null,
        payUrl: null,
        deeplink: null,
        status: 'failed',
        resultCode: -1,
        message: 'Tạo QR thất bại',
      });
      setSnackbar({
        open: true,
        message: 'Tạo QR thất bại',
        severity: 'error',
      });
    } finally {
      setLoading(false);
    }
  };

  const startStatusCheck = () => {
    if (statusCheckInterval.current) {
      clearInterval(statusCheckInterval.current);
    }

    // Check payment status every 3 seconds
    statusCheckInterval.current = setInterval(async () => {
      if (!result?.orderId) {
        return;
      }

      try {
        const response = await fetch(`/api/momo/check-status/${result.orderId}`, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('access_token')}`,
          },
        });

        const data: { status: string } = await response.json();

        if (data.status === 'success') {
          setPaymentStatus('success');
          if (statusCheckInterval.current) {
            clearInterval(statusCheckInterval.current);
          }

          setSnackbar({
            open: true,
            message: 'Thanh toán thành công!',
            severity: 'success',
          });
        } else if (data.status === 'failed') {
          setPaymentStatus('failed');
          if (statusCheckInterval.current) {
            clearInterval(statusCheckInterval.current);
          }

          setSnackbar({
            open: true,
            message: 'Thanh toán thất bại!',
            severity: 'error',
          });
        } else {
          setPaymentStatus('checking');
        }
      } catch (error) {
        console.error('Error checking payment status:', error);
      }
    }, 3000);
  };

  const resetPayment = () => {
    setResult(null);
    setPaymentStatus('pending');
    if (statusCheckInterval.current) {
      clearInterval(statusCheckInterval.current);
    }
  };

  const handleSnackbarClose = () => {
    setSnackbar((prev) => ({ ...prev, open: false }));
  };

  const renderStatusChip = () => {
    let color: 'default' | 'success' | 'error' | 'info' = 'default';
    let label = 'Chờ thanh toán';
    if (paymentStatus === 'checking') {
      color = 'info';
      label = 'Đang kiểm tra...';
    } else if (paymentStatus === 'success') {
      color = 'success';
      label = 'Thanh toán thành công';
    } else if (paymentStatus === 'failed') {
      color = 'error';
      label = 'Thanh toán thất bại';
    }
    return <Chip color={color} label={label} size="small" />;
  };

  return (
    <Container maxWidth="lg">
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" sx={{ mb: 1 }}>
          MoMo QR
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Tạo mã QR / link thanh toán MoMo và theo dõi trạng thái theo thời gian thực.
        </Typography>
      </Box>

      <Grid container spacing={3}>
        <Grid item xs={12} md={4}>
          <Card>
            <CardContent component="form" onSubmit={handleSubmit}>
              <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 2 }}>
                Thông tin thanh toán
              </Typography>
              <TextField
                label="Tên khách hàng"
                value={customerName}
                onChange={(e) => setCustomerName(e.target.value)}
                required
                fullWidth
                size="small"
                sx={{ mb: 2 }}
              />
              <TextField
                label="Số tiền (VNĐ)"
                type="number"
                inputProps={{ min: 1000 }}
                value={totalAmount}
                onChange={(e) => setTotalAmount(e.target.value)}
                required
                fullWidth
                size="small"
                sx={{ mb: 2 }}
              />
              <TextField
                select
                label="Loại giao dịch"
                value={transactionType}
                onChange={(e) => setTransactionType(e.target.value as 'Đáo' | 'Rút')}
                fullWidth
                size="small"
                sx={{ mb: 3 }}
              >
                <MenuItem value="Đáo">Đáo</MenuItem>
                <MenuItem value="Rút">Rút</MenuItem>
              </TextField>
              <Button
                type="submit"
                fullWidth
                variant="contained"
                disabled={loading}
              >
                {loading ? 'Đang tạo...' : 'Tạo QR thanh toán'}
              </Button>
            </CardContent>
          </Card>
        </Grid>

        <Grid item xs={12} md={8}>
          {result && (
            <Card>
              <CardContent>
                <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2, flexWrap: 'wrap', gap: 1 }}>
                  <Typography variant="subtitle1" fontWeight={600}>
                    Chọn phương thức thanh toán
                  </Typography>
                  {renderStatusChip()}
                </Box>

                <Grid container spacing={2}>
                  {result.qrCodeUrl && (
                    <Grid item xs={12} sm={6}>
                      <Paper
                        variant="outlined"
                        sx={{ p: 2, textAlign: 'center', height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}
                      >
                        <Typography variant="subtitle2" sx={{ mb: 1 }}>
                          Quét mã QR
                        </Typography>
                        <Box
                          component="img"
                          src={result.qrCodeUrl}
                          alt="MoMo QR"
                          sx={{ width: 220, height: 220, objectFit: 'contain', mb: 1 }}
                        />
                        <Typography variant="body2" color="text.secondary">
                          Mở app MoMo → Quét mã
                        </Typography>
                      </Paper>
                    </Grid>
                  )}

                  {result.deeplink && (
                    <Grid item xs={12} sm={6}>
                      <Paper
                        variant="outlined"
                        sx={{ p: 2, textAlign: 'center', height: '100%', display: 'flex', flexDirection: 'column', alignItems: 'center', justifyContent: 'center' }}
                      >
                        <Typography variant="subtitle2" sx={{ mb: 1 }}>
                          Mở App MoMo
                        </Typography>
                        <Box sx={{ fontSize: 56, mb: 2 }}>💳</Box>
                        <Button
                          href={result.deeplink ?? ''}
                          variant="contained"
                          fullWidth
                        >
                          Mở App MoMo
                        </Button>
                        <Typography variant="body2" color="text.secondary" sx={{ mt: 1 }}>
                          Chỉ hoạt động trên điện thoại
                        </Typography>
                      </Paper>
                    </Grid>
                  )}
                </Grid>

                {result.payUrl && (
                  <Box sx={{ mt: 3 }}>
                    <Typography variant="subtitle2" sx={{ mb: 1 }}>
                      Thanh toán trực tiếp (Web)
                    </Typography>
                    <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
                      Trang thanh toán sẽ tải tự động bên dưới:
                    </Typography>
                    <Paper
                      variant="outlined"
                      sx={{
                        border: '2px solid #A50064',
                        borderRadius: 2,
                        overflow: 'hidden',
                      }}
                    >
                      <iframe
                        ref={iframeRef}
                        src={result.payUrl ?? ''}
                        title="MoMo Payment"
                        width="100%"
                        height={520}
                        frameBorder={0}
                        allow="payment"
                        style={{ background: 'white' }}
                      />
                    </Paper>
                  </Box>
                )}

                <Box sx={{ mt: 3, textAlign: 'center' }}>
                  <Button variant="outlined" onClick={resetPayment}>
                    Tạo mã mới
                  </Button>
                </Box>
              </CardContent>
            </Card>
          )}
        </Grid>
      </Grid>

      <Snackbar
        open={snackbar.open}
        autoHideDuration={4000}
        onClose={handleSnackbarClose}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
      >
        <Alert
          onClose={handleSnackbarClose}
          severity={snackbar.severity}
          variant="filled"
          sx={{ width: '100%' }}
        >
          {snackbar.message}
        </Alert>
      </Snackbar>
    </Container>
  );
}
