import { useState } from 'react';
import { getDailyAdvances, settleDailyAdvances } from '../api/settlements';
import { AgentSelect } from '../components/AgentSelect';

function toDDMMYYYY(d: Date) {
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

export function DailyAdvances() {
  const [agentId, setAgentId] = useState<number | ''>('');
  const [date, setDate] = useState(toDDMMYYYY(new Date()));
  const [data, setData] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(false);
  const [settling, setSettling] = useState(false);

  const load = () => {
    if (!agentId) return;
    setLoading(true);
    getDailyAdvances(agentId, date)
      .then(setData)
      .finally(() => setLoading(false));
  };

  const handleSettle = async () => {
    if (!agentId) return;
    setSettling(true);
    try {
      await settleDailyAdvances(agentId, date);
      load();
    } finally {
      setSettling(false);
    }
  };

  return (
    <div className="daily-advances-page">
      <h1>Tạm ứng theo ngày</h1>
      <div className="filters card">
        <label>
          Đại lý
          <AgentSelect value={agentId} onChange={setAgentId} />
        </label>
        <label>
          Ngày (dd/mm/yyyy)
          <input
            value={date}
            onChange={(e) => setDate(e.target.value)}
            placeholder="01/01/2025"
          />
        </label>
        <button type="button" className="btn btn-primary" onClick={load} disabled={!agentId || loading}>
          {loading ? 'Đang tải...' : 'Xem'}
        </button>
      </div>
      {data && (
        <div className="card">
          <pre className="data-json">{JSON.stringify(data, null, 2)}</pre>
          <button
            type="button"
            className="btn btn-primary"
            onClick={handleSettle}
            disabled={settling}
          >
            {settling ? 'Đang đối soát...' : 'Đối soát tạm ứng'}
          </button>
        </div>
      )}
    </div>
  );
}
