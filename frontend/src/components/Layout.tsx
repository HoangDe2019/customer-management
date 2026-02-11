import { Link, NavLink, useNavigate } from 'react-router-dom';
import {
  AppBar,
  Toolbar,
  Typography,
  Button,
  Box,
  Chip,
} from '@mui/material';
import { useAuth } from '../context/AuthContext';
import { logout } from '../api/auth';

const navItems = [
  { to: '/', label: 'Tổng quan' },
  { to: '/agents', label: 'Đại lý' },
  { to: '/transactions', label: 'Giao dịch' },
  { to: '/transactions/export', label: 'Xuất GD' },
  { to: '/daily-advances', label: 'Tạm ứng' },
  { to: '/settlements', label: 'Cuối ngày' },
  { to: '/settlements/history', label: 'Lịch sử đối soát' },
  { to: '/statistics', label: 'Thống kê' },
  { to: '/momo', label: 'MoMo QR' },
  { to: '/cccd', label: 'Quét CCCD' },
  { to: '/queue', label: 'Queue' },
];

export function Layout({ children }: { children: React.ReactNode }) {
  const { user } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      <AppBar position="static" elevation={0}>
        <Toolbar variant="dense" sx={{ flexWrap: 'wrap', gap: 0.5 }}>
          <Typography
            component={Link}
            to="/"
            variant="h6"
            sx={{ color: 'white', textDecoration: 'none', mr: 2 }}
          >
            Customer Management
          </Typography>
          {navItems.map((item) => (
            <NavLink key={item.to} to={item.to} style={{ textDecoration: 'none' }}>
              {({ isActive }) => (
                <Button
                  sx={{
                    color: 'white',
                    ...(isActive ? { bgcolor: 'rgba(255,255,255,0.2)' } : {}),
                  }}
                >
                  {item.label}
                </Button>
              )}
            </NavLink>
          ))}
          <Box sx={{ flexGrow: 1 }} />
          <Typography variant="body2" sx={{ color: 'white', mr: 1 }}>
            {user?.name}
          </Typography>
          {user?.is_admin && (
            <Chip label="Admin" size="small" sx={{ mr: 1, bgcolor: 'warning.main', color: 'black' }} />
          )}
          <Button color="inherit" onClick={handleLogout}>
            Đăng xuất
          </Button>
        </Toolbar>
      </AppBar>
      <Box component="main" sx={{ flex: 1, p: 2, maxWidth: 1400, mx: 'auto', width: '100%' }}>
        {children}
      </Box>
    </Box>
  );
}
