import { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getTransaction, updateTransactionStatus } from '../api/transactions';
import type { Transaction } from '../types';

const STATUS_OPTIONS = [
  'Chờ duyệt',
  'Đã duyệt',
  'Đang xử lý',
  'Chờ DR',
  'Hoàn thành',
  'Thất bại',
  'Đã hủy',
];

function formatMoney(n: number) {
  return new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(n) + ' ₫';
}

function formatDate(s: string) {
  if (!s) return '-';
  return new Date(s).toLocaleString('vi-VN');
}

export function TransactionDetail() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const [transaction, setTransaction] = useState<Transaction | null>(null);
  const [loading, setLoading] = useState(true);
  const [updating, setUpdating] = useState(false);

  useEffect(() => {
    if (!id) return;
    getTransaction(Number(id))
      .then(setTransaction)
      .catch(() => setTransaction(null))
      .finally(() => setLoading(false));
  }, [id]);

  const handleStatusChange = async (newStatus: string) => {
    if (!transaction) return;
    setUpdating(true);
    try {
      const updated = await updateTransactionStatus(transaction.id, newStatus);
      setTransaction(updated);
    } finally {
      setUpdating(false);
    }
  };

  if (loading) return <div className="page-loading">Đang tải...</div>;
  if (!transaction) return <div className="alert alert-error">Không tìm thấy giao dịch.</div>;

  const t = transaction;

  return (
    <div className="transaction-detail">
      <div className="page-header">
        <h1>Chi tiết giao dịch: {t.transaction_id}</h1>
        <button type="button" className="btn btn-ghost" onClick={() => navigate(-1)}>
          Quay lại
        </button>
      </div>

      <div className="card detail-grid">
        <div className="detail-row">
          <span className="label">Khách hàng</span>
          <span>{t.customer_name}</span>
        </div>
        <div className="detail-row">
          <span className="label">CCCD</span>
          <span>{t.cccd_number || '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Đại lý</span>
          <span>{t.agent?.name ?? '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Loại</span>
          <span>{t.transaction_type}</span>
        </div>
        <div className="detail-row">
          <span className="label">Số tiền</span>
          <span>{formatMoney(Number(t.total_amount))}</span>
        </div>
        <div className="detail-row">
          <span className="label">Phí POS</span>
          <span>{t.pos_fee_amount != null ? formatMoney(Number(t.pos_fee_amount)) : '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Phí đại lý</span>
          <span>{t.agent_fee_amount != null ? formatMoney(Number(t.agent_fee_amount)) : '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Lợi nhuận</span>
          <span>{t.profit != null ? formatMoney(Number(t.profit)) : '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Tạm ứng</span>
          <span>{t.agent_advance != null ? formatMoney(Number(t.agent_advance)) : '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Hoàn trả đại lý</span>
          <span>{t.refund_to_agent != null ? formatMoney(Number(t.refund_to_agent)) : '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Đối soát ròng</span>
          <span>{t.net_settlement != null ? formatMoney(Number(t.net_settlement)) : '-'}</span>
        </div>
        <div className="detail-row">
          <span className="label">Trạng thái</span>
          <span>
            <select
              value={t.status}
              onChange={(e) => handleStatusChange(e.target.value)}
              disabled={updating}
            >
              {STATUS_OPTIONS.map((s) => (
                <option key={s} value={s}>{s}</option>
              ))}
            </select>
          </span>
        </div>
        <div className="detail-row">
          <span className="label">Ngày giao dịch</span>
          <span>{formatDate(t.transaction_date)}</span>
        </div>
      </div>

      {t.logs && t.logs.length > 0 && (
        <div className="card">
          <h3>Lịch sử thay đổi</h3>
          <ul className="log-list">
            {t.logs.map((log) => (
              <li key={log.id}>
                {log.action}: {log.old_value ?? '-'} → {log.new_value ?? '-'} ({formatDate(log.created_at)})
              </li>
            ))}
          </ul>
        </div>
      )}
    </div>
  );
}
