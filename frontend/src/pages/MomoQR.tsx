import React, { useState } from 'react';
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
  const [snackbar, setSnackbar] = useState<{
    open: boolean;
    message: string;
    severity: 'success' | 'error' | 'info'
  }>({
    open: false,
    message: '',
    severity: 'info',
  });

  // ✅ Remove polling logic - không cần nữa vì đã có notification

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    const amount = parseFloat(totalAmount);
    if (isNaN(amount) || amount < 1000) {
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
      // ✅ Call API - sẽ wait for notification và return data
      const qrData = await generateMoMoQR({
        customer_name: customerName,
        total_amount: amount,
        transaction_type: transactionType,
      });


      setResult(qrData);
      setSnackbar({
        open: true,
        message: 'Tạo QR thành công! Vui lòng thanh toán.',
        severity: 'success',
      });

    } catch (error) {
      setSnackbar({
        open: true,
        message: error instanceof Error ? error.message : 'Tạo QR thất bại',
        severity: 'error',
      });

      // Set error result
      setResult({
        paymentId: null,
        orderId: null,
        qrCodeUrl: null,
        payUrl: null,
        deeplink: null,
        status: 'failed',
        resultCode: -1,
        message: error instanceof Error ? error.message : 'Tạo QR thất bại',
      });
    } finally {
      setLoading(false);
    }
  };

  const resetPayment = () => {
    setResult(null);
    setCustomerName('');
    setTotalAmount('');
  };

  const handleSnackbarClose = () => {
    setSnackbar((prev) => ({ ...prev, open: false }));
  };

  const renderStatusChip = () => {
    if (!result) return null;

    if (result.resultCode === 0) {
      return <Chip color="success" label="Chờ thanh toán" size="small" />;
    } else {
      return <Chip color="error" label="Tạo QR thất bại" size="small" />;
    }
  };

  return (
    <Container maxWidth="lg">
      <Box sx={{ mb: 3 }}>
        <Typography variant="h5" sx={{ mb: 1 }}>
          MoMo QR
        </Typography>
        <Typography variant="body2" color="text.secondary">
          Tạo mã QR / link thanh toán MoMo
        </Typography>
      </Box>

      <Grid container spacing={3}>
        {/* Form Section */}
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
                {loading ? 'Đang xử lý...' : 'Tạo QR thanh toán'}
              </Button>

              {loading && (
                <Typography
                  variant="caption"
                  color="text.secondary"
                  sx={{ display: 'block', mt: 1, textAlign: 'center' }}
                >
                  Đang chờ phản hồi từ MoMo...
                </Typography>
              )}
            </CardContent>
          </Card>
        </Grid>

        {/* Result Section */}
        <Grid item xs={12} md={8}>
          {result && (
            <Card>
              <CardContent>
                <Box sx={{
                  display: 'flex',
                  justifyContent: 'space-between',
                  alignItems: 'center',
                  mb: 2,
                  flexWrap: 'wrap',
                  gap: 1
                }}>
                  <Typography variant="subtitle1" fontWeight={600}>
                    {result.resultCode === 0
                      ? 'Chọn phương thức thanh toán'
                      : 'Lỗi tạo QR'}
                  </Typography>
                  {renderStatusChip()}
                </Box>

                {/* ✅ Only show payment options if successful */}
                {result.resultCode === 0 && (
                  <Grid container spacing={2}>
                    {/* QR Code */}
                    {result.qrCodeUrl && (
                      <Grid item xs={12} sm={6}>
                        <Paper
                          variant="outlined"
                          sx={{
                            p: 2,
                            textAlign: 'center',
                            height: '100%',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            justifyContent: 'center'
                          }}
                        >
                          <Typography variant="subtitle2" sx={{ mb: 1 }}>
                            Quét mã QR
                          </Typography>
                          <Box
                            component="img"
                            src={result.qrCodeUrl}
                            alt="MoMo QR"
                            sx={{
                              width: 220,
                              height: 220,
                              objectFit: 'contain',
                              mb: 1
                            }}
                          />
                          <Typography variant="body2" color="text.secondary">
                            Mở app MoMo → Quét mã
                          </Typography>
                        </Paper>
                      </Grid>
                    )}

                    {/* Deeplink */}
                    {result.deeplink && (
                      <Grid item xs={12} sm={6}>
                        <Paper
                          variant="outlined"
                          sx={{
                            p: 2,
                            textAlign: 'center',
                            height: '100%',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            justifyContent: 'center'
                          }}
                        >
                          <Typography variant="subtitle2" sx={{ mb: 1 }}>
                            Mở App MoMo
                          </Typography>
                          <Box sx={{ fontSize: 56, mb: 2 }}>💳</Box>
                          <Button
                            href={result.deeplink}
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
                )}

                {/* Pay URL - Web Payment */}
                {result.payUrl && result.resultCode === 0 && (
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
                        src={result.payUrl}
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

                {/* Error Message */}
                {result.resultCode !== 0 && (
                  <Box sx={{ mt: 2 }}>
                    <Alert severity="error">
                      <Typography variant="body2">
                        <strong>Lỗi:</strong> {result.message}
                      </Typography>
                      <Typography variant="caption" color="text.secondary">
                        Mã lỗi: {result.resultCode}
                      </Typography>
                    </Alert>
                  </Box>
                )}

                {/* Order Info */}
                {result.orderId && (
                  <Box sx={{ mt: 2, p: 2, bgcolor: 'grey.50', borderRadius: 1 }}>
                    <Typography variant="caption" color="text.secondary">
                      Order ID: <strong>{result.orderId}</strong>
                    </Typography>
                    <br />
                    <Typography variant="caption" color="text.secondary">
                      Payment ID: <strong>{result.paymentId}</strong>
                    </Typography>
                  </Box>
                )}

                {/* Reset Button */}
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

      {/* Snackbar */}
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
