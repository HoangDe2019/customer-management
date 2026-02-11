import { useState } from 'react';
import { getEodSettlement, saveEodSettlement } from '../api/settlements';
import { AgentSelect } from '../components/AgentSelect';

function toDDMMYYYY(d: Date) {
  const day = String(d.getDate()).padStart(2, '0');
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const year = d.getFullYear();
  return `${day}/${month}/${year}`;
}

export function SettlementEod() {
  const [agentId, setAgentId] = useState<number | ''>('');
  const [date, setDate] = useState(toDDMMYYYY(new Date()));
  const [data, setData] = useState<Record<string, unknown> | null>(null);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);

  const load = () => {
    if (!agentId) return;
    setLoading(true);
    getEodSettlement(agentId, date)
      .then(setData)
      .finally(() => setLoading(false));
  };

  const handleSave = async () => {
    if (!agentId || !data) return;
    setSaving(true);
    try {
      await saveEodSettlement(agentId, date, data as Record<string, unknown>);
      load();
    } finally {
      setSaving(false);
    }
  };

  return (
    <div className="settlement-eod-page">
      <h1>Đối soát cuối ngày</h1>
      <div className="filters card">
        <label>
          Đại lý
          <AgentSelect value={agentId} onChange={setAgentId} />
        </label>
        <label>
          Ngày (dd/mm/yyyy)
          <input value={date} onChange={(e) => setDate(e.target.value)} placeholder="01/01/2025" />
        </label>
        <button type="button" className="btn btn-primary" onClick={load} disabled={!agentId || loading}>
          {loading ? 'Đang tải...' : 'Tính đối soát'}
        </button>
      </div>
      {data && (
        <div className="card">
          <pre className="data-json">{JSON.stringify(data, null, 2)}</pre>
          <button
            type="button"
            className="btn btn-primary"
            onClick={handleSave}
            disabled={saving}
          >
            {saving ? 'Đang lưu...' : 'Lưu đối soát cuối ngày'}
          </button>
        </div>
      )}
    </div>
  );
}
