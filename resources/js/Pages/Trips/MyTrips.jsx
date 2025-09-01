import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

/* ---------- UI helpers ---------- */
const hBtn = { height: '32px' }; // compact buttons everywhere

function StatusBadge({ status }) {
  const map = {
    'scheduled'  : 'bg-slate-100 text-slate-700 dark:bg-slate-800/60 dark:text-slate-300',
    'dispatched' : 'bg-sky-100 text-sky-700 dark:bg-sky-800/60 dark:text-sky-300',
    'in-progress': 'bg-amber-100 text-amber-700 dark:bg-amber-800/60 dark:text-amber-200',
    'completed'  : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-800/60 dark:text-emerald-200',
    'cancelled'  : 'bg-rose-100 text-rose-700 dark:bg-rose-800/60 dark:text-rose-200',
  };
  return (
    <span className={`px-2 py-0.5 rounded-full text-xs ${map[status] || 'bg-slate-100 text-slate-700'}`}>
      {status}
    </span>
  );
}

function Section({ title, trips, onComplete }) {
  const PAGE = 6;
  const [page, setPage] = useState(1);
  const shown = useMemo(() => trips?.slice(0, page * PAGE) ?? [], [trips, page]);

  if (!trips || trips.length === 0) return null;

  return (
    <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4">
      <div className="flex items-center justify-between mb-3">
        <h3 className="text-lg font-semibold text-slate-800 dark:text-slate-100">{title}</h3>
        <div className="text-xs text-slate-500 dark:text-slate-400">{trips.length} total</div>
      </div>

      <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
        {shown.map((t) => (
          <TripCard key={t.id} trip={t} onComplete={onComplete} />
        ))}
      </div>

      {shown.length < trips.length && (
        <div className="mt-3 flex justify-center">
          <button
            onClick={() => setPage((p) => p + 1)}
            className="px-3 py-1.5 rounded-lg border text-sm bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-gray-50"
            style={hBtn}
          >
            Load more
          </button>
        </div>
      )}
    </section>
  );
}

function TripCard({ trip, onComplete }) {
  const { patch, processing, setData, data, reset } = useForm({ action: '', note: '' });
  const [noteOpen, setNoteOpen] = useState(false);

  const startTrip = () => {
    setData('action', 'start');
    patch(route('trips.driver-status', trip.id), { onSuccess: () => reset() });
  };

  const submitComplete = (e) => {
    e.preventDefault();
    setData('action', 'complete');
    patch(route('trips.driver-status', trip.id), {
      onSuccess: () => {
        setNoteOpen(false);
        reset();
        onComplete?.();
      },
    });
  };

  // Free Google Maps deeplink (no API/billing)
  const deepLink = trip.to_lat != null && trip.to_lng != null
    ? `https://www.google.com/maps/dir/?api=1&destination=${trip.to_lat},${trip.to_lng}`
    : (trip.destination ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(trip.destination)}` : null);

  return (
    <div className="rounded-xl border border-gray-100 dark:border-slate-800 bg-white dark:bg-slate-900 p-4 space-y-3">
      <div className="flex items-center justify-between">
        <div className="text-xs sm:text-sm text-slate-500 dark:text-slate-400">Trip #{trip.id}</div>
        <StatusBadge status={trip.status} />
      </div>

      <div className="text-[13px] leading-5 text-slate-700 dark:text-slate-200">
        <div><span className="text-slate-500 dark:text-slate-400">Vehicle:</span> {trip.vehicle?.label} {trip.vehicle?.plate ? `(${trip.vehicle.plate})` : ''}</div>
        <div><span className="text-slate-500 dark:text-slate-400">Direction:</span> {trip.direction || '—'}</div>
        <div><span className="text-slate-500 dark:text-slate-400">Origin:</span> {trip.origin || '—'}</div>
        <div><span className="text-slate-500 dark:text-slate-400">Destination:</span> {trip.destination || '—'}</div>
        <div><span className="text-slate-500 dark:text-slate-400">Depart:</span> {trip.depart_at || '—'}</div>
        {trip.return_at && <div><span className="text-slate-500 dark:text-slate-400">Return:</span> {trip.return_at}</div>}
      </div>

      {/* Actions */}
      <div className="flex flex-wrap gap-2 pt-1">
        {['scheduled', 'dispatched'].includes(trip.status) && (
         <button
        onClick={startTrip}
        disabled={processing}
        className="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700"
        >
        Start Trip
        </button>
        )}

        {trip.status === 'in-progress' && (
          <button
  onClick={() => setNoteOpen(true)}
  className="inline-flex items-center justify-center h-8 px-3 rounded-lg bg-amber-600 text-white text-sm font-medium hover:bg-amber-700"
>
  Complete Trip
</button>
        )}

        {/* View opens shared Trips/Show (maps + contacts) */}
        <Link
  href={route('trips.show', trip.id)}
  className="inline-flex items-center justify-center h-8 px-3 rounded-lg border text-sm font-medium bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-gray-50"
>
  View
</Link>

        {/* Optional deeplink */}
        {deepLink && (
  <a
    href={deepLink}
    target="_blank"
    rel="noreferrer"
    className="inline-flex items-center justify-center h-8 px-3 rounded-lg text-sm font-medium border bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100/60 dark:hover:bg-emerald-900/30"
  >
    Open in Maps
  </a>
)}
      </div>

      {/* Note modal */}
      {noteOpen && (
        <div className="fixed inset-0 z-40 flex items-center justify-center bg-black/40 p-4">
          <form onSubmit={submitComplete} className="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl p-4 shadow border border-gray-100 dark:border-slate-800">
            <h4 className="font-semibold mb-2 text-slate-800 dark:text-slate-100">Add completion note (optional)</h4>
            <textarea
              className="w-full border rounded-lg p-2 bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-slate-800 dark:text-slate-100"
              rows={4}
              value={data.note}
              onChange={(e) => setData('note', e.target.value)}
              placeholder="e.g., Returned vehicle at the office, handed keys to security."
            />
            <div className="mt-3 flex items-center gap-2">
              <button disabled={processing} className="px-4 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700" style={hBtn}>Submit</button>
              <button type="button" onClick={() => setNoteOpen(false)} className="px-4 rounded-lg border text-sm bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-slate-700 dark:text-slate-200" style={hBtn}>Cancel</button>
            </div>
          </form>
        </div>
      )}
    </div>
  );
}

export default function MyTrips({ today = [], upcoming = [], recent_completed = [], manager = null }) {
  const refresh = () => window.location.reload();

  // quick counters for a compact KPI row (optional, nice polish)
  const counts = {
    today: today?.length ?? 0,
    upcoming: upcoming?.length ?? 0,
    completed: recent_completed?.length ?? 0,
  };

  return (
    <AuthenticatedLayout header={<h2 className="text-2xl font-bold text-slate-800 dark:text-slate-100">My Trips</h2>}>
      <Head title="My Trips" />

      <div className="p-6 space-y-6 bg-gray-50 dark:bg-slate-950 min-h-screen">
        {/* KPI strip */}
        <div className="grid grid-cols-3 gap-3">
          {[
            {label: 'Today', value: counts.today},
            {label: 'Upcoming', value: counts.upcoming},
            {label: 'Recently Completed', value: counts.completed},
          ].map((k) => (
            <div key={k.label} className="rounded-2xl bg-white dark:bg-slate-900 border border-gray-100 dark:border-slate-800 p-3 text-center">
              <div className="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">{k.label}</div>
              <div className="text-2xl font-bold text-slate-800 dark:text-slate-100">{k.value}</div>
            </div>
          ))}
        </div>

        {/* Manager card */}
        {manager && (
          <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4">
            <div className="text-sm text-slate-500 dark:text-slate-400 mb-2">Logistics Manager</div>
            <div className="flex items-center gap-3">
              <img
                src={manager.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(manager.name)}&background=0ea5e9&color=fff`}
                alt={manager.name}
                className="h-12 w-12 rounded-full object-cover ring-2 ring-sky-500/20"
              />
              <div className="min-w-0">
                <div className="font-medium truncate text-slate-800 dark:text-slate-100">{manager.name}</div>
                {manager.phone && (
                  <a href={`tel:${manager.phone}`} className="text-sm text-emerald-700 dark:text-emerald-300 hover:underline">
                    {manager.phone}
                  </a>
                )}
                <div className="text-xs text-slate-500 dark:text-slate-400">{manager.email}</div>
              </div>
              <div className="ml-auto flex gap-2">
                {manager.phone && (
                  <>
                    <a href={`tel:${manager.phone}`} className="px-3 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700" style={hBtn}>Call</a>
                    <a href={`sms:${manager.phone}`} className="px-3 rounded-lg border text-sm bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-slate-700 dark:text-slate-200" style={hBtn}>SMS</a>
                  </>
                )}
              </div>
            </div>
          </section>
        )}

        {/* Lists */}
        <Section title="Today" trips={today} onComplete={refresh} />
        <Section title="Upcoming" trips={upcoming} onComplete={refresh} />
        <Section title="Recently Completed" trips={recent_completed} />

        <div className="pt-2">
          <Link href={route('trips.index')} className="text-slate-600 dark:text-slate-300 hover:underline">All Trips</Link>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
