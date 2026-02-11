import { useState } from 'react';
import { Link, useNavigate, useLocation } from 'react-router-dom';
import { Box, Card, CardContent, Typography, TextField, Button, Alert, Fade } from '@mui/material';
import { login } from '../api/auth';
import { useAuth } from '../context/AuthContext';

export function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const { setUser } = useAuth();
  const from = (location.state as { from?: { pathname: string } })?.from?.pathname ?? '/';

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);
    try {
      const data = await login(email, password);
      setUser(data.user);
      navigate(from, { replace: true });
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string; error?: string } } };
      const msg = ax?.response?.data?.message ?? ax?.response?.data?.error ?? 'Đăng nhập thất bại';
      setError(Array.isArray(msg) ? msg[0] : String(msg));
    } finally {
      setLoading(false);
    }
  };

  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        background: 'linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%)',
        p: 2,
      }}
    >
      <Fade in>
        <Card sx={{ maxWidth: 400, width: '100%', boxShadow: 8 }}>
          <CardContent sx={{ p: 3 }}>
            <Typography variant="h5" fontWeight={700} gutterBottom align="center">
              Đăng nhập
            </Typography>
            <form onSubmit={handleSubmit}>
              {error && (
                <Alert severity="error" onClose={() => setError('')} sx={{ mb: 2 }}>
                  {error}
                </Alert>
              )}
              <TextField
                fullWidth
                label="Email"
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                required
                autoComplete="email"
                sx={{ mb: 2 }}
              />
              <TextField
                fullWidth
                label="Mật khẩu"
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                required
                autoComplete="current-password"
                sx={{ mb: 2 }}
              />
              <Button
                fullWidth
                type="submit"
                variant="contained"
                size="large"
                disabled={loading}
                sx={{ py: 1.5, mb: 2 }}
              >
                {loading ? 'Đang đăng nhập...' : 'Đăng nhập'}
              </Button>
            </form>
            <Typography variant="body2" color="text.secondary" align="center">
              Chưa có tài khoản? <Link to="/register">Đăng ký</Link>
            </Typography>
          </CardContent>
        </Card>
      </Fade>
    </Box>
  );
}
