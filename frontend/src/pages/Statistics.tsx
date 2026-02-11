import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { getStatistics } from '../api/statistics';
import { getUserAgents } from '../api/agents';
import type { Statistics as StatsType } from '../types';
import type { Agent } from '../types';

function formatMoney(n: number) {
  return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n) + ' ₫';
}

export function Statistics() {
  const [searchParams] = useSearchParams();
  const agentIdParam = searchParams.get('agent_id');
  const [agents, setAgents] = useState<Agent[]>([]);
  const [agentId, setAgentId] = useState<number | ''>(() => {
    if (agentIdParam) {
      const n = parseInt(agentIdParam, 10);
      return isNaN(n) ? '' : n;
    }
    return '';
  });
  const [period, setPeriod] = useState<'all' | 'today' | '3days' | 'week' | 'month'>('all');
  const [data, setData] = useState<StatsType | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    getUserAgents().then(setAgents);
  }, []);

  useEffect(() => {
    if (!agentId) {
      setData(null);
      return;
    }
    setLoading(true);
    getStatistics(agentId, period)
      .then(setData)
      .finally(() => setLoading(false));
  }, [agentId, period]);

  return (
    <div className="statistics-page">
      <h1>Thống kê (chỉ giao dịch Hoàn thành)</h1>
      <div className="filters card">
        <label>
          Đại lý
          <select
            value={agentId === '' ? '' : agentId}
            onChange={(e) => setAgentId(e.target.value ? Number(e.target.value) : '')}
          >
            <option value="">Chọn đại lý</option>
            {agents.map((a) => (
              <option key={a.id} value={a.id}>{a.name}</option>
            ))}
          </select>
        </label>
        <label>
          Khoảng thời gian
          <select
            value={period}
            onChange={(e) => setPeriod(e.target.value as 'all' | 'today' | '3days' | 'week' | 'month')}
          >
            <option value="all">Tất cả</option>
            <option value="today">Hôm nay</option>
            <option value="3days">3 ngày</option>
            <option value="week">Tuần</option>
            <option value="month">Tháng</option>
          </select>
        </label>
      </div>
      {loading && <div className="loading-inline">Đang tải...</div>}
      {data && (
        <div className="stats-cards card">
          <div className="stat-row">
            <span>Tổng giao dịch</span>
            <strong>{data.total_transactions}</strong>
          </div>
          <div className="stat-row">
            <span>Tổng tiền</span>
            <strong>{formatMoney(data.total_amount)}</strong>
          </div>
          <div className="stat-row">
            <span>Lợi nhuận</span>
            <strong>{formatMoney(data.total_profit)}</strong>
          </div>
          <div className="stat-row">
            <span>Tạm ứng đại lý</span>
            <strong>{formatMoney(data.total_agent_advance)}</strong>
          </div>
          <div className="stat-row">
            <span>Đáo</span>
            <strong>{data.dao_count}</strong>
          </div>
          <div className="stat-row">
            <span>Rút</span>
            <strong>{data.rut_count}</strong>
          </div>
          {data.by_status && Object.keys(data.by_status).length > 0 && (
            <div className="by-status">
              <h4>Theo trạng thái</h4>
              <ul>
                {Object.entries(data.by_status).map(([status, cnt]) => (
                  <li key={status}>{status}: {cnt}</li>
                ))}
              </ul>
            </div>
          )}
          {data.recent && data.recent.length > 0 && (
            <div className="recent-list">
              <h4>Giao dịch gần đây</h4>
              <table className="data-table">
                <thead>
                  <tr>
                    <th>Khách</th>
                    <th>Số tiền</th>
                    <th>Loại</th>
                    <th>Ngày</th>
                    <th>Trạng thái</th>
                  </tr>
                </thead>
                <tbody>
                  {data.recent.map((r, i) => (
                    <tr key={i}>
                      <td>{r.name}</td>
                      <td>{formatMoney(r.amount)}</td>
                      <td>{r.type}</td>
                      <td>{r.date}</td>
                      <td>{r.status}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
