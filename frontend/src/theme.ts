import { createTheme } from '@mui/material/styles';

export const theme = createTheme({
  palette: {
    primary: { main: '#f7f7f4' },
    secondary: { main: '#64748b' },
    background: { default: '#f0f2f5', paper: '#ffffff' },
  },
  typography: {
    fontFamily: '"Segoe UI", system-ui, sans-serif',
    h5: {
      fontWeight: 600,
    },
  },
  shape: { borderRadius: 8 },
  transitions: {
    duration: {
      shortest: 150,
      shorter: 200,
      short: 250,
      standard: 300,
      complex: 375,
      enteringScreen: 225,
      leavingScreen: 195,
    },
  },
  components: {
    MuiCard: {
      styleOverrides: {
        root: {
          transition: 'box-shadow 0.3s ease, transform 0.2s ease',
        },
      },
    },
    MuiButton: {
      styleOverrides: {
        root: {
          textTransform: 'none',
          borderRadius: 999,
        },
      },
    },
    MuiAppBar: {
      defaultProps: {
        elevation: 0,
        color: 'primary',
      },
    },
    MuiPaper: {
      styleOverrides: {
        rounded: {
          borderRadius: 12,
        },
      },
    },
  },
});
