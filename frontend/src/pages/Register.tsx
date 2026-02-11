import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Box, Card, CardContent, Typography, TextField, Button, Alert, Fade } from '@mui/material';
import { register } from '../api/auth';
import { useAuth } from '../context/AuthContext';

export function Register() {
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { setUser } = useAuth();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (password !== passwordConfirmation) {
      setError('Mật khẩu xác nhận không khớp');
      return;
    }
    setLoading(true);
    try {
      const data = await register({ name, email, password, password_confirmation: passwordConfirmation });
      setUser(data.user);
      navigate('/', { replace: true });
    } catch (err: unknown) {
      const data = (err as { response?: { data?: Record<string, unknown> } })?.response?.data;
      const msg =
        (data?.message as string) ||
        (Array.isArray(data?.errors) ? (data?.errors as string[]).flat().join(', ') : '') ||
        (data?.error as string) ||
        'Đăng ký thất bại';
      setError(String(msg));
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
              Đăng ký
            </Typography>
            <form onSubmit={handleSubmit}>
              {error && (
                <Alert severity="error" onClose={() => setError('')} sx={{ mb: 2 }}>
                  {error}
                </Alert>
              )}
              <TextField fullWidth label="Họ tên" value={name} onChange={(e) => setName(e.target.value)} required autoComplete="name" sx={{ mb: 2 }} />
              <TextField fullWidth label="Email" type="email" value={email} onChange={(e) => setEmail(e.target.value)} required autoComplete="email" sx={{ mb: 2 }} />
              <TextField fullWidth label="Mật khẩu (tối thiểu 8 ký tự)" type="password" value={password} onChange={(e) => setPassword(e.target.value)} required inputProps={{ minLength: 8 }} autoComplete="new-password" sx={{ mb: 2 }} />
              <TextField fullWidth label="Xác nhận mật khẩu" type="password" value={passwordConfirmation} onChange={(e) => setPasswordConfirmation(e.target.value)} required autoComplete="new-password" sx={{ mb: 2 }} />
              <Button fullWidth type="submit" variant="contained" size="large" disabled={loading} sx={{ py: 1.5, mb: 2 }}>
                {loading ? 'Đang đăng ký...' : 'Đăng ký'}
              </Button>
            </form>
            <Typography variant="body2" color="text.secondary" align="center">
              Đã có tài khoản? <Link to="/login">Đăng nhập</Link>
            </Typography>
          </CardContent>
        </Card>
      </Fade>
    </Box>
  );
}
