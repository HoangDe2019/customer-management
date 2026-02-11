import { Link, NavLink, useNavigate } from 'react-router-dom';
import React from 'react';
import {
  AppBar,
  Toolbar,
  Typography,
  Button,
  Box,
  Chip,
  IconButton,
  Drawer,
  List,
  ListItem,
  ListItemButton,
  ListItemText,
  Divider,
  useMediaQuery,
} from '@mui/material';
import MenuIcon from '@mui/icons-material/Menu';
import { useTheme } from '@mui/material/styles';
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
  const theme = useTheme();
  const isMobile = useMediaQuery(theme.breakpoints.down('md'));
  const [mobileOpen, setMobileOpen] = React.useState(false);

  const handleLogout = async () => {
    await logout();
    navigate('/login');
  };

  const toggleMobile = () => {
    setMobileOpen((prev) => !prev);
  };

  const handleNavClick = (to: string) => {
    navigate(to);
    setMobileOpen(false);
  };

  return (
    <Box sx={{ display: 'flex', flexDirection: 'column', minHeight: '100vh' }}>
      <AppBar position="static">
        <Toolbar
          variant="dense"
          sx={{
            gap: 1,
            px: { xs: 1.5, sm: 2, md: 3 },
          }}
        >
          {isMobile && (
            <IconButton
              color="inherit"
              edge="start"
              onClick={toggleMobile}
              aria-label="Mở menu"
              sx={{ mr: 1 }}
            >
              <MenuIcon />
            </IconButton>
          )}
          <Typography
            component={Link}
            to="/"
            variant="h6"
            sx={{ color: 'inherit', textDecoration: 'none', mr: 2, fontWeight: 600 }}
          >
            Customer Management
          </Typography>
          {!isMobile && (
            <>
              {navItems.map((item) => (
                <NavLink key={item.to} to={item.to} style={{ textDecoration: 'none' }}>
                  {({ isActive }) => (
                    <Button
                      sx={{
                        color: 'inherit',
                        borderRadius: 999,
                        px: 1.5,
                        ...(isActive ? { bgcolor: 'rgba(255,255,255,0.18)' } : {}),
                      }}
                    >
                      {item.label}
                    </Button>
                  )}
                </NavLink>
              ))}
              <Box sx={{ flexGrow: 1 }} />
            </>
          )}
          <Box sx={{ flexGrow: 1 }} />
          <Typography variant="body2" sx={{ color: 'inherit', mr: 1, display: { xs: 'none', sm: 'block' } }}>
            {user?.name}
          </Typography>
          {user?.is_admin && (
            <Chip
              label="Admin"
              size="small"
              sx={{ mr: 1, bgcolor: 'warning.main', color: 'black', display: { xs: 'none', sm: 'inline-flex' } }}
            />
          )}
          <Button color="inherit" onClick={handleLogout}>
            Đăng xuất
          </Button>
        </Toolbar>
      </AppBar>

      <Drawer
        anchor="left"
        open={isMobile && mobileOpen}
        onClose={toggleMobile}
        ModalProps={{ keepMounted: true }}
      >
        <Box sx={{ width: 260, pt: 1 }}>
          <Box sx={{ px: 2, py: 1.5 }}>
            <Typography variant="subtitle1" fontWeight={600}>
              Menu
            </Typography>
            {user && (
              <Typography variant="body2" color="text.secondary">
                {user.name}
              </Typography>
            )}
          </Box>
          <Divider />
          <List>
            {navItems.map((item) => (
              <ListItem key={item.to} disablePadding>
                <ListItemButton onClick={() => handleNavClick(item.to)}>
                  <ListItemText primary={item.label} />
                </ListItemButton>
              </ListItem>
            ))}
          </List>
        </Box>
      </Drawer>

      <Box
        component="main"
        sx={{
          flex: 1,
          px: { xs: 1.5, sm: 2, md: 3 },
          py: { xs: 2, sm: 3 },
          maxWidth: 1400,
          mx: 'auto',
          width: '100%',
        }}
      >
        {children}
      </Box>
    </Box>
  );
}
