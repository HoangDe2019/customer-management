import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { createTransaction } from '../api/transactions';
import { getConfig } from '../api/config';
import { AgentSelect } from '../components/AgentSelect';
import type { Config } from '../types';

export function TransactionCreate() {
  const navigate = useNavigate();
  const [agentId, setAgentId] = useState<number | ''>('');
  const [customerName, setCustomerName] = useState('');
  const [cccdNumber, setCccdNumber] = useState('');
  const [totalAmount, setTotalAmount] = useState('');
  const [transactionType, setTransactionType] = useState<'Đáo' | 'Rút'>('Đáo');
  const [posFeePercent, setPosFeePercent] = useState('');
  const [agentFeePercent, setAgentFeePercent] = useState('');
  const [agentAdvance, setAgentAdvance] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const [config, setConfig] = useState<Config | null>(null);

  useEffect(() => {
    getConfig().then(setConfig).catch(() => {});
  }, []);

  useEffect(() => {
    if (config) {
      if (!posFeePercent) setPosFeePercent(String(config.default_pos_fee));
      if (!agentFeePercent) setAgentFeePercent(String(config.default_agent_fee));
    }
  }, [config]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    if (!agentId) {
      setError('Vui lòng chọn đại lý');
      return;
    }
    const amount = parseFloat(totalAmount);
    if (isNaN(amount) || amount < 0) {
      setError('Số tiền không hợp lệ');
      return;
    }
    setLoading(true);
    try {
      const t = await createTransaction({
        agent_id: agentId,
        customer_name: customerName || undefined,
        cccd_number: cccdNumber || undefined,
        total_amount: amount,
        transaction_type: transactionType,
        pos_fee_percent: posFeePercent ? parseFloat(posFeePercent) : undefined,
        agent_fee_percent: agentFeePercent ? parseFloat(agentFeePercent) : undefined,
        agent_advance: agentAdvance ? parseFloat(agentAdvance) : 0,
      });
      navigate(`/transactions/${t.id}`);
    } catch (err: unknown) {
      const ax = err as { response?: { data?: { message?: string; error?: string } } };
      const msg = ax?.response?.data?.message ?? ax?.response?.data?.error ?? 'Tạo giao dịch thất bại';
      setError(String(msg));
    } finally {
      setLoading(false);
    }
  };

  const suggestions = config?.amount_suggestions ?? [];

  return (
    <div className="transaction-form-page">
      <h1>Tạo giao dịch</h1>
      <form onSubmit={handleSubmit} className="card form-card">
        {error && <div className="alert alert-error">{error}</div>}
        <label>
          Đại lý *
          <AgentSelect value={agentId} onChange={setAgentId} required />
        </label>
        <label>
          Loại giao dịch *
          <select
            value={transactionType}
            onChange={(e) => setTransactionType(e.target.value as 'Đáo' | 'Rút')}
          >
            <option value="Đáo">Đáo</option>
            <option value="Rút">Rút</option>
          </select>
        </label>
        <label>
          Tên khách hàng
          <input
            value={customerName}
            onChange={(e) => setCustomerName(e.target.value)}
            placeholder="Để trống sẽ tự tạo mã KH"
          />
        </label>
        <label>
          Số CCCD (12 số)
          <input
            value={cccdNumber}
            onChange={(e) => setCccdNumber(e.target.value)}
            maxLength={12}
            placeholder="Tùy chọn"
          />
        </label>
        <label>
          Số tiền (VNĐ) *
          <input
            type="number"
            min={0}
            step={1000}
            value={totalAmount}
            onChange={(e) => setTotalAmount(e.target.value)}
            required
          />
        </label>
        {suggestions.length > 0 && (
          <div className="amount-suggestions">
            {suggestions.map((n) => (
              <button
                key={n}
                type="button"
                className="btn btn-sm btn-ghost"
                onClick={() => setTotalAmount(String(n))}
              >
                {(n / 1_000_000).toFixed(0)}M
              </button>
            ))}
          </div>
        )}
        <label>
          % Phí POS
          <input
            type="number"
            step="0.001"
            value={posFeePercent}
            onChange={(e) => setPosFeePercent(e.target.value)}
          />
        </label>
        <label>
          % Phí đại lý
          <input
            type="number"
            step="0.001"
            value={agentFeePercent}
            onChange={(e) => setAgentFeePercent(e.target.value)}
          />
        </label>
        <label>
          Tạm ứng đại lý (VNĐ)
          <input
            type="number"
            min={0}
            value={agentAdvance}
            onChange={(e) => setAgentAdvance(e.target.value)}
          />
        </label>
        <div className="form-actions">
          <button type="button" className="btn btn-ghost" onClick={() => navigate(-1)}>
            Hủy
          </button>
          <button type="submit" className="btn btn-primary" disabled={loading}>
            {loading ? 'Đang tạo...' : 'Tạo giao dịch'}
          </button>
        </div>
      </form>
    </div>
  );
}
