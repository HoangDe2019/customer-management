import { useEffect, useState } from 'react';
import { getSettlementHistory } from '../api/settlements';
import { AgentSelect } from '../components/AgentSelect';
import type { PaginatedResponse } from '../types';
import type { EodSettlementRecord } from '../api/settlements';

function formatDate(s: string) {
  if (!s) return '-';
  return new Date(s).toLocaleDateString('vi-VN');
}

export function SettlementHistory() {
  const [agentId, setAgentId] = useState<number | ''>('');
  const [data, setData] = useState<PaginatedResponse<EodSettlementRecord> | null>(null);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    setLoading(true);
    getSettlementHistory({
      agent_id: agentId || undefined,
      page,
      per_page: 15,
    })
      .then(setData)
      .finally(() => setLoading(false));
  }, [agentId, page]);

  const items = data?.data ?? [];
  const lastPage = data?.last_page ?? 1;

  return (
    <div className="settlement-history-page">
      <h1>Lịch sử đối soát</h1>
      <div className="filters card">
        <label>
          Đại lý
          <AgentSelect value={agentId} onChange={(id) => { setAgentId(id); setPage(1); }} />
        </label>
      </div>
      {loading && <div className="loading-inline">Đang tải...</div>}
      <div className="table-wrap">
        <table className="data-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Đại lý</th>
              <th>Ngày đối soát</th>
              <th>Người đối soát</th>
            </tr>
          </thead>
          <tbody>
            {items.map((r) => (
              <tr key={r.id}>
                <td>{r.id}</td>
                <td>{r.agent?.name ?? '-'}</td>
                <td>{formatDate(r.settlement_date)}</td>
                <td>{r.settler?.name ?? '-'}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div className="pagination">
        <button
          type="button"
          className="btn btn-ghost"
          disabled={page <= 1}
          onClick={() => setPage((p) => p - 1)}
        >
          Trước
        </button>
        <span>Trang {page} / {lastPage}</span>
        <button
          type="button"
          className="btn btn-ghost"
          disabled={page >= lastPage}
          onClick={() => setPage((p) => p + 1)}
        >
          Sau
        </button>
      </div>
    </div>
  );
}
