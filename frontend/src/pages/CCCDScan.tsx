import { useState, useRef } from 'react';
import {
  Box,
  Button,
  Card,
  CardContent,
  Typography,
  Alert,
  CircularProgress,
  Paper,
  Divider,
} from '@mui/material';
import { scanCCCD } from '../api/transactions';

export function CCCDScan() {
  const [result, setResult] = useState<{
    cccdNumber?: string;
    fullName?: string;
    confidence?: number;
    error?: string;
  } | null>(null);
  const [loading, setLoading] = useState(false);
  const [preview, setPreview] = useState<string | null>(null);
  const [fileName, setFileName] = useState<string>('');
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFile = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;
    setResult(null);
    setPreview(null);
    setFileName(file.name);

    const reader = new FileReader();
    reader.onload = async () => {
      const dataUrl = reader.result as string;
      setPreview(dataUrl);
      const base64 = dataUrl.includes(',') ? dataUrl.split(',')[1]! : dataUrl;
      setLoading(true);
      try {
        const res = await scanCCCD(base64);
        setResult(res);
      } catch {
        setResult({ error: 'Gọi API thất bại. Kiểm tra GEMINI_API_KEY và kết nối.' });
      } finally {
        setLoading(false);
      }
    };
    reader.onerror = () => {
      setResult({ error: 'Đọc file thất bại' });
      setLoading(false);
    };
    reader.readAsDataURL(file);
  };

  const reset = () => {
    setResult(null);
    setPreview(null);
    setFileName('');
    setLoading(false);
    if (fileInputRef.current) fileInputRef.current.value = '';
  };

  return (
    <Box sx={{ maxWidth: 720, mx: 'auto' }}>
      <Typography variant="h5" sx={{ mb: 2 }} fontWeight={600}>
        Quét CCCD (Gemini AI)
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 2 }}>
        Tải lên ảnh CCCD Việt Nam (JPEG/PNG). Hệ thống trích xuất số CCCD và họ tên qua Gemini 2.0 Flash.
      </Typography>

      <Card variant="outlined" sx={{ mb: 2 }}>
        <CardContent>
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            onChange={handleFile}
            style={{ display: 'none' }}
            id="cccd-file"
          />
          <label htmlFor="cccd-file">
            <Button
              component="span"
              variant="outlined"
              disabled={loading}
              fullWidth
              sx={{ py: 2, borderStyle: 'dashed' }}
            >
              Chọn ảnh CCCD (upload image)
            </Button>
          </label>
          {fileName && (
            <Typography variant="caption" display="block" sx={{ mt: 1 }} color="text.secondary">
              {fileName}
            </Typography>
          )}
        </CardContent>
      </Card>

      {loading && (
        <Box sx={{ display: 'flex', alignItems: 'center', gap: 2, mb: 2 }}>
          <CircularProgress size={24} />
          <Typography variant="body2">Đang trích xuất thông tin...</Typography>
        </Box>
      )}

      {preview && !loading && (
        <Paper variant="outlined" sx={{ p: 2, mb: 2, textAlign: 'center' }}>
          <Typography variant="subtitle2" gutterBottom>
            Ảnh đã tải
          </Typography>
          <Box
            component="img"
            src={preview}
            alt="CCCD preview"
            sx={{ maxHeight: 240, maxWidth: '100%', borderRadius: 1 }}
          />
          <Button size="small" onClick={reset} sx={{ mt: 1 }}>
            Chọn ảnh khác
          </Button>
        </Paper>
      )}

      {result && (
        <Card sx={{ bgcolor: result.error ? 'error.50' : 'primary.50' }}>
          <CardContent>
            {result.error ? (
              <Alert severity="error" onClose={reset}>
                {result.error}
              </Alert>
            ) : (
              <>
                <Typography variant="subtitle1" fontWeight={600} sx={{ mb: 1 }}>
                  Kết quả trích xuất
                </Typography>
                <Divider sx={{ my: 2 }} />
                <Box component="dl" sx={{ m: 0 }}>
                  {result.cccdNumber && (
                    <>
                      <Typography component="dt" variant="caption" color="text.secondary">
                        Số CCCD
                      </Typography>
                      <Typography component="dd" variant="h6" sx={{ mt: 0.25, mb: 1.5 }}>
                        {result.cccdNumber}
                      </Typography>
                    </>
                  )}
                  {result.fullName && (
                    <>
                      <Typography component="dt" variant="caption" color="text.secondary">
                        Họ và tên
                      </Typography>
                      <Typography component="dd" variant="h6" sx={{ mt: 0.25, mb: 1.5 }}>
                        {result.fullName}
                      </Typography>
                    </>
                  )}
                  {result.confidence != null && (
                    <>
                      <Typography component="dt" variant="caption" color="text.secondary">
                        Độ tin cậy
                      </Typography>
                      <Typography component="dd" variant="body2" sx={{ mt: 0.25 }}>
                        {result.confidence}%
                      </Typography>
                    </>
                  )}
                </Box>
                <Button size="small" onClick={reset}>
                  Quét ảnh khác
                </Button>
              </>
            )}
          </CardContent>
        </Card>
      )}
    </Box>
  );
}
