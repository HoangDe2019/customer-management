import { Navigate, Route, Routes } from 'react-router-dom';
import { ProtectedRoute } from './components/ProtectedRoute';
import { Layout } from './components/Layout';
import { Login } from './pages/Login';
import { Register } from './pages/Register';
import { Dashboard } from './pages/Dashboard';
import { Agents } from './pages/Agents';
import { Transactions } from './pages/Transactions';
import { TransactionCreate } from './pages/TransactionCreate';
import { TransactionDetail } from './pages/TransactionDetail';
import { TransactionExport } from './pages/TransactionExport';
import { DailyAdvances } from './pages/DailyAdvances';
import { SettlementEod } from './pages/SettlementEod';
import { SettlementHistory } from './pages/SettlementHistory';
import { Statistics } from './pages/Statistics';
import { MomoQR } from './pages/MomoQR';
import { CCCDScan } from './pages/CCCDScan';
import { Queue } from './pages/Queue';

function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/register" element={<Register />} />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Layout>
              <Dashboard />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/agents"
        element={
          <ProtectedRoute>
            <Layout>
              <Agents />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/transactions"
        element={
          <ProtectedRoute>
            <Layout>
              <Transactions />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/transactions/new"
        element={
          <ProtectedRoute>
            <Layout>
              <TransactionCreate />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/transactions/export"
        element={
          <ProtectedRoute>
            <Layout>
              <TransactionExport />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/transactions/:id"
        element={
          <ProtectedRoute>
            <Layout>
              <TransactionDetail />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/daily-advances"
        element={
          <ProtectedRoute>
            <Layout>
              <DailyAdvances />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/settlements"
        element={
          <ProtectedRoute>
            <Layout>
              <SettlementEod />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/settlements/history"
        element={
          <ProtectedRoute>
            <Layout>
              <SettlementHistory />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/statistics"
        element={
          <ProtectedRoute>
            <Layout>
              <Statistics />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/momo"
        element={
          <ProtectedRoute>
            <Layout>
              <MomoQR />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/cccd"
        element={
          <ProtectedRoute>
            <Layout>
              <CCCDScan />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route
        path="/queue"
        element={
          <ProtectedRoute>
            <Layout>
              <Queue />
            </Layout>
          </ProtectedRoute>
        }
      />
      <Route path="*" element={<Navigate to="/" replace />} />
    </Routes>
  );
}

export default App;
