// resources/js/Components/ConsignmentTimeline.jsx
import React from 'react';

const STATUS_META = {
  loaded:            { label: 'Loaded',            klass: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200' },
  enroute:           { label: 'Enroute',           klass: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200' },
  onsite:            { label: 'On Site',           klass: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200' },
  delivered:         { label: 'Delivered',         klass: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' },
  return_collected:  { label: 'Return Collected',  klass: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200' },
  return_delivered:  { label: 'Return Delivered',  klass: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200' },
  failed:            { label: 'Failed',            klass: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200' },
};

function formatWhen(dtStr) {
  try {
    const d = new Date(dtStr);
    if (Number.isNaN(d.getTime())) return dtStr ?? '';
    return d.toLocaleString([], { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  } catch {
    return dtStr ?? '';
  }
}

export default function ConsignmentTimeline({ consignmentId, auto = true, intervalMs = 10000 }) {
  const [events, setEvents] = React.useState([]);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState(null);
  const [running, setRunning] = React.useState(auto);

  const fetchEvents = React.useCallback(async () => {
    setError(null);
    try {
      const res = await fetch(`/consignments/${consignmentId}/events`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
      });
      const text = await res.text();
      let json = null; try { json = JSON.parse(text); } catch {}
      if (!res.ok) throw new Error((json && (json.message || json.error)) || text || 'Failed to load events');

      // accept any of: [ ... ]  OR  { ok:true, data:[ ... ] }  OR  { events:[ ... ] }
      const list =
        Array.isArray(json) ? json :
        (Array.isArray(json?.data) ? json.data :
        (Array.isArray(json?.events) ? json.events : []));

      // normalize fields a bit for the UI
      const normalized = list.map(e => ({
        ...e,
        type: e.type ?? e.status ?? 'event',
        occurred_at: e.occurred_at ?? e.created_at ?? null,
        by_user: e.by_user ?? e.user ?? null,
        photos: Array.isArray(e.photos) ? e.photos : (Array.isArray(e.photos_json) ? e.photos_json : []),
        signature_url: e.signature_url ?? e.signature ?? null,
      }));
      setEvents(normalized);
    } catch (e) {
      setError(e.message || 'Failed to load events');
    } finally {
      setLoading(false);
    }
  }, [consignmentId]);

  React.useEffect(() => {
    fetchEvents();
    if (!running) return;
    const t = setInterval(fetchEvents, Math.max(4000, Number(intervalMs) || 10000));
    return () => clearInterval(t);
  }, [fetchEvents, running, intervalMs]);

  return (
    <div className="border rounded-xl p-4 bg-white dark:bg-gray-800">
      <div className="flex items-center justify-between mb-3">
        <h4 className="font-semibold text-gray-800 dark:text-gray-100">Custody Timeline</h4>
        <div className="flex items-center gap-2">
          <span className={`inline-flex items-center text-xs px-2 py-0.5 rounded ${running ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200'}`}>
            {running ? 'Auto-refresh: ON' : 'Auto-refresh: OFF'}
          </span>
          <button
            className="text-xs px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
            onClick={() => setRunning(r => !r)}
            type="button"
          >
            {running ? 'Pause' : 'Resume'}
          </button>
          <button
            className="text-xs px-2 py-1 rounded bg-indigo-100 hover:bg-indigo-200 dark:bg-indigo-900/40 dark:hover:bg-indigo-900/60"
            onClick={fetchEvents}
            type="button"
          >
            Refresh
          </button>
        </div>
      </div>

      {loading ? (
        <div className="text-sm text-gray-500 dark:text-gray-300">Loading…</div>
      ) : error ? (
        <div className="text-sm text-rose-600 dark:text-rose-300">Error: {error}</div>
      ) : events.length === 0 ? (
        <div className="text-sm text-gray-500 dark:text-gray-300">No events yet.</div>
      ) : (
        <ol className="relative border-s border-gray-200 dark:border-gray-700 ps-4">
          {events.map((ev) => {
            const meta = STATUS_META[ev.type] || { label: ev.type, klass: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' };
            return (
              <li key={ev.id} className="mb-5 ms-2">
                <span className="absolute -start-3 mt-1 flex h-5 w-5 items-center justify-center rounded-full bg-white ring-2 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                  <span className="h-2.5 w-2.5 rounded-full bg-gray-400 dark:bg-gray-500" />
                </span>

                <div className="flex flex-wrap items-center gap-2">
                  <span className={`inline-flex items-center text-xs font-medium px-2 py-0.5 rounded ${meta.klass}`}>
                    {meta.label}
                  </span>
                  <span className="text-xs text-gray-500 dark:text-gray-400">{formatWhen(ev.occurred_at)}</span>
                  {ev.by_user?.name && (
                    <span className="text-xs text-gray-500 dark:text-gray-400">by {ev.by_user.name}</span>
                  )}
                </div>

                <div className="mt-1 text-sm text-gray-800 dark:text-gray-100">
                  {ev.receiver_name && (
                    <div>
                      Receiver: <span className="font-medium">{ev.receiver_name}</span>
                      {ev.receiver_phone ? ` (${ev.receiver_phone})` : ''}
                    </div>
                  )}
                  {ev.otp_used && (
                    <div>OTP: <span className="font-mono tracking-wider">{ev.otp_used}</span></div>
                  )}
                  {ev.note && <div className="italic text-gray-600 dark:text-gray-300">“{ev.note}”</div>}
                  {(ev.lat && ev.lng) && (
                    <div className="text-xs text-gray-500 dark:text-gray-400">GPS: {ev.lat}, {ev.lng}</div>
                  )}
                </div>

                {/* Photos */}
                {Array.isArray(ev.photos) && ev.photos.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-2">
                    {ev.photos.map((p, i) => {
                      const url = typeof p === 'string' ? p : (p.url || p.path);
                      return (
                        <a
                          key={i}
                          href={url}
                          target="_blank"
                          rel="noreferrer"
                          className="block border rounded overflow-hidden w-24 h-24 bg-gray-50 dark:bg-gray-900"
                          title="View photo"
                        >
                          <img src={url} alt={`proof-${i}`} className="w-full h-full object-cover" />
                        </a>
                      );
                    })}
                  </div>
                )}

                {/* Signature (if present) */}
                {ev.signature_url && (
                  <a href={ev.signature_url} target="_blank" rel="noreferrer" className="block mt-1">
                    <img
                      src={ev.signature_url}
                      alt="Signature"
                      className="h-16 rounded border border-gray-200 dark:border-slate-700"
                    />
                  </a>
                )}
              </li>
            );
          })}
        </ol>
      )}
    </div>
  );
}
