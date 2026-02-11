import { useState } from 'react';
import { exportTransactions } from '../api/transactions';
import { AgentSelect } from '../components/AgentSelect';

export function TransactionExport() {
  const [agentId, setAgentId] = useState<number | ''>('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [status, setStatus] = useState('all');
  const [type, setType] = useState('all');
  const [result, setResult] = useState<Awaited<ReturnType<typeof exportTransactions>> | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleExport = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!agentId) {
      setError('Chọn đại lý');
      return;
    }
    if (!dateFrom || !dateTo) {
      setError('Chọn khoảng ngày (định dạng dd/mm/yyyy)');
      return;
    }
    setError('');
    setLoading(true);
    setResult(null);
    try {
      const data = await exportTransactions({
        agent_id: agentId,
        date_from: dateFrom,
        date_to: dateTo,
        status: status !== 'all' ? status : undefined,
        type: type !== 'all' ? type : undefined,
      });
      setResult(data);
    } catch {
      setError('Export thất bại');
    } finally {
      setLoading(false);
    }
  };

  const formatMoney = (n: number) =>
    new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n) + ' ₫';

  return (
    <div className="export-page">
      <h1>Xuất giao dịch</h1>
      <form onSubmit={handleExport} className="card form-card">
        {error && <div className="alert alert-error">{error}</div>}
        <label>
          Đại lý *
          <AgentSelect value={agentId} onChange={setAgentId} required />
        </label>
        <label>
          Từ ngày (dd/mm/yyyy) *
          <input
            value={dateFrom}
            onChange={(e) => setDateFrom(e.target.value)}
            placeholder="01/01/2025"
          />
        </label>
        <label>
          Đến ngày (dd/mm/yyyy) *
          <input
            value={dateTo}
            onChange={(e) => setDateTo(e.target.value)}
            placeholder="31/01/2025"
          />
        </label>
        <label>
          Trạng thái
          <select value={status} onChange={(e) => setStatus(e.target.value)}>
            <option value="all">Tất cả</option>
            <option value="Chờ duyệt">Chờ duyệt</option>
            <option value="Đã duyệt">Đã duyệt</option>
            <option value="Đang xử lý">Đang xử lý</option>
            <option value="Chờ DR">Chờ DR</option>
            <option value="Hoàn thành">Hoàn thành</option>
            <option value="Thất bại">Thất bại</option>
            <option value="Đã hủy">Đã hủy</option>
          </select>
        </label>
        <label>
          Loại
          <select value={type} onChange={(e) => setType(e.target.value)}>
            <option value="all">Tất cả</option>
            <option value="Đáo">Đáo</option>
            <option value="Rút">Rút</option>
          </select>
        </label>
        <button type="submit" className="btn btn-primary" disabled={loading}>
          {loading ? 'Đang xuất...' : 'Xuất dữ liệu'}
        </button>
      </form>

      {result && (
        <div className="card export-result">
          <h3>Kết quả: {result.date_from} – {result.date_to}</h3>
          <p>Số bản ghi: {result.record_count}</p>
          <div className="summary-grid">
            <div><strong>Tổng tiền:</strong> {formatMoney(result.summary.total_amount)}</div>
            <div><strong>Lợi nhuận:</strong> {formatMoney(result.summary.total_profit)}</div>
            <div><strong>Hoàn trả đại lý:</strong> {formatMoney(result.summary.total_refund_to_agent)}</div>
            <div><strong>Tạm ứng:</strong> {formatMoney(result.summary.total_advance)}</div>
            <div><strong>Đối soát ròng:</strong> {formatMoney(result.summary.net_settlement)}</div>
          </div>
          <button
            type="button"
            className="btn btn-ghost"
            onClick={() => {
              const blob = new Blob([JSON.stringify(result, null, 2)], { type: 'application/json' });
              const a = document.createElement('a');
              a.href = URL.createObjectURL(blob);
              a.download = `export-${result.date_from}-${result.date_to}.json`;
              a.click();
            }}
          >
            Tải JSON
          </button>
        </div>
      )}
    </div>
  );
}
