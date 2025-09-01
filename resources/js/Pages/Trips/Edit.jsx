// resources/js/Pages/Trips/Edit.jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm, usePage, router } from '@inertiajs/react';
import { useState } from 'react';

/* ⬇⬇⬇ ADD THIS HELPER RIGHT HERE (after imports) */
// helper: consistent, readable item text (handles numeric "unit")
function fmtItem(i) {
  const desc = (i.description ?? '').trim();
  const qty  = (i.quantity ?? '').toString().trim();
  const unit = (i.unit ?? '').toString().trim();
  const numericUnit = /^[0-9.]+$/.test(unit);
  const unitLabel = unit ? (numericUnit ? ' units' : ` ${unit}`) : '';
  return `${desc} × ${qty}${unitLabel}`;
}


/* ---------- Shared classes (light/dark safe) ---------- */
const fieldBase =
  "h-10 w-full rounded-lg border px-3 pr-10 text-[15px] " +
  "bg-white text-slate-800 placeholder-slate-400 border-gray-200 " +
  "focus:outline-none focus:ring-2 focus:ring-emerald-500/50 focus:border-emerald-500 " +
  "dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-500 dark:border-slate-700";

const btnPrimary =
  "inline-flex items-center justify-center h-9 px-3 rounded-lg bg-emerald-600 text-white text-sm font-medium " +
  "hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/50";

const btnGhost =
  "inline-flex items-center justify-center h-9 px-3 rounded-lg border text-sm font-medium " +
  "bg-white text-slate-700 border-gray-200 hover:bg-slate-50 " +
  "dark:bg-slate-900 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-800";

const btnTiny =
  "mt-2 inline-flex items-center justify-center h-8 px-3 rounded-lg text-xs font-medium " +
  "bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200";

/* ---------- Reusable Select with custom chevron ---------- */
function Select({ value, onChange, children, className = "" }) {
  return (
    <div className="relative w-full sm:w-auto">
      <select
        value={value}
        onChange={onChange}
        className={`${fieldBase} appearance-none ${className}`}
      >
        {children}
      </select>
      {/* overlay chevron so text never collides with native arrow */}
      <svg
        className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-500 dark:text-slate-400"
        viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
      >
        <path
          fillRule="evenodd"
          d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
          clipRule="evenodd"
        />
      </svg>
    </div>
  );
}

/* -------------------- DropsBuilder -------------------- */
function DropsBuilder() {
  const { trip, ports = [], vessels = [] } = usePage().props;

  const [rows, setRows] = useState([
    { destination: 'onne', port_id: '', vessel_id: '', items: [], return_expected: false, destination_label: '' },
  ]);

  const addRow = () =>
    setRows(prev => [
      ...prev,
      { destination: 'onne', port_id: '', vessel_id: '', items: [], return_expected: false, destination_label: '' },
    ]);

  const removeRow = (idx) => setRows(prev => prev.filter((_, i) => i !== idx));

 const updateRow = (idx, key, val) =>
  setRows(prev => {
    const next = [...prev];
    const row  = { ...next[idx], [key]: val };

    if (key === 'destination') {
      if (val === 'guest_house') {
        row.port_id = '';
        row.vessel_id = '';
        row.destination_label = 'Guest House'; // auto
      } else {
        row.destination_label = '';
      }
    }
    next[idx] = row;
    return next;
  });


  const addItem = (idx) =>
    setRows(prev => {
      const next = [...prev];
      next[idx].items = [...(next[idx].items || []), { description: '', quantity: 1, unit: '' }];
      return next;
    });

  const updateItem = (rowIdx, itemIdx, key, val) =>
    setRows(prev => {
      const next = [...prev];
      const items = [...(next[rowIdx].items || [])];
      items[itemIdx] = { ...items[itemIdx], [key]: val };
      next[rowIdx].items = items;
      return next;
    });

  const removeItem = (rowIdx, itemIdx) =>
    setRows(prev => {
      const next = [...prev];
      next[rowIdx].items = (next[rowIdx].items || []).filter((_, i) => i !== itemIdx);
      return next;
    });

  const [submitting, setSubmitting] = useState(false);
   const submit = (e) => {
    e.preventDefault();
    setSubmitting(true);
    router.post(
      route('trips.drops.store', trip.id),
      { drops: rows },
      {
        preserveScroll: true,
        onFinish: () => setSubmitting(false),
        onSuccess: () => {
          // Stay on Edit, or redirect from controller—either is fine.
          // If you stay here, existing consignments will render (see steps below)
        },
      }
    );
  };

  return (
    <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4 space-y-3 mt-6 text-slate-800 dark:text-slate-100">
      <div className="flex items-center justify-between">
        <h3 className="text-base font-semibold">Drops (Ports & Vessels)</h3>
        <button type="button" onClick={addRow} className={btnPrimary}>
          Add drop
        </button>
      </div>

      {rows.map((r, idx) => (
        <div key={idx} className="rounded-xl border border-gray-200 dark:border-slate-700 p-3 space-y-2">
          {/* Row controls */}
          <div className="flex flex-wrap items-center gap-2">
            {/* destination */}
            <Select value={r.destination} onChange={(e)=>updateRow(idx,'destination', e.target.value)}>
              <option value="onne">Onne (Port + Vessels)</option>
              <option value="guest_house">Guest House</option>
            </Select>

            {/* when destination === 'onne' */}
            {r.destination === 'onne' && (
              <>
                <Select value={r.port_id} onChange={(e)=>updateRow(idx,'port_id', e.target.value)}>
                  <option value="">Select port</option>
                  {ports.map(p => (
                    <option key={p.id} value={p.id}>{p.code}</option>
                  ))}
                </Select>

                <Select className="min-w-[12rem]" value={r.vessel_id} onChange={(e)=>updateRow(idx,'vessel_id', e.target.value)}>
                  <option value="">Select vessel</option>
                  {vessels.map(v => (
                    <option key={v.id} value={v.id}>{v.name}</option>
                  ))}
                </Select>
              </>
            )}

            {/* when destination === 'guest_house' */}
            {r.destination === 'guest_house' && (
                <span className="px-2 py-1 rounded-lg text-sm bg-slate-100 text-slate-700
                                dark:bg-slate-800 dark:text-slate-200">
                    Guest House
                </span>
                )}



            <label className="inline-flex items-center gap-2 ml-auto text-sm text-slate-700 dark:text-slate-200">
              <input
                type="checkbox"
                className="h-4 w-4 accent-emerald-600"
                checked={!!r.return_expected}
                onChange={(e)=>updateRow(idx,'return_expected', e.target.checked)}
              />
              Return expected
            </label>

            <button type="button" onClick={()=>removeRow(idx)} className={btnGhost}>
              Remove
            </button>
          </div>

          {/* Items */}
          <div className="pl-1">
            <div className="text-xs text-slate-500 dark:text-slate-400">Items (optional)</div>
            {(r.items || []).map((it, j) => (
              <div key={j} className="mt-2 flex flex-wrap items-center gap-2">
                <input
                  type="text"
                  placeholder="Description"
                  className={`${fieldBase} flex-1`}
                  value={it.description}
                  onChange={(e)=>updateItem(idx, j, 'description', e.target.value)}
                />
                <input
                  type="number"
                  min="1"
                  className={`${fieldBase} w-24 text-center`}
                  value={it.quantity}
                  onChange={(e)=>updateItem(idx, j, 'quantity', e.target.value)}
                />
                <input
                    type="text"
                    placeholder="Unit (eg,.pcs, kg, ltr)"
                    className="h-9 w-24.5 px-2 rounded-lg border bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700"
                    value={it.unit || ''}
                    onChange={(e) => updateItem(idx, j, 'unit', e.target.value)}
                    />

                <button type="button" onClick={()=>removeItem(idx, j)} className={btnGhost}>
                  Remove
                </button>
              </div>
            ))}
            <button type="button" onClick={()=>addItem(idx)} className={btnTiny}>
              + Add item
            </button>
          </div>
        </div>
      ))}

      <div className="pt-2">
        <button onClick={submit} disabled={submitting} className="h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm">
          Save drops to trip
        </button>
      </div>
    </section>
  );
}

/* -------------------- Page -------------------- */
export default function Edit() {
  const { trip, consignments = [] } = usePage().props;

  // Minimal trip fields form (logic unchanged)
  const { data, setData, patch, processing } = useForm({
    status: trip?.status ?? 'scheduled',
    depart_at: trip?.depart_at ?? '',
    return_at: trip?.return_at ?? '',
    notes: trip?.notes ?? '',
  });

  const saveTrip = (e) => {
    e.preventDefault();
    patch(route('trips.update', trip.id));
  };

  return (
    <AuthenticatedLayout header={<h2 className="text-2xl font-bold text-slate-900 dark:text-white">Edit Trip #{trip?.id}</h2>}>
      <Head title={`Edit Trip #${trip?.id}`} />
      <div className="p-6 space-y-6">
        {/* Trip details form */}
        <form
          onSubmit={saveTrip}
          className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4 space-y-3"
        >
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label className="block text-sm text-slate-600 dark:text-slate-300 mb-1">Status</label>
              <select
                className={`${fieldBase} pr-10`}
                value={data.status}
                onChange={(e)=>setData('status', e.target.value)}
              >
                <option>scheduled</option>
                <option>dispatched</option>
                <option>in-progress</option>
                <option>completed</option>
                <option>cancelled</option>
              </select>
            </div>
            <div>
              <label className="block text-sm text-slate-600 dark:text-slate-300 mb-1">Depart</label>
              <input
                type="datetime-local"
                className={fieldBase}
                value={data.depart_at || ''}
                onChange={(e)=>setData('depart_at', e.target.value)}
              />
            </div>
            <div>
              <label className="block text-sm text-slate-600 dark:text-slate-300 mb-1">Return</label>
              <input
                type="datetime-local"
                className={fieldBase}
                value={data.return_at || ''}
                onChange={(e)=>setData('return_at', e.target.value)}
              />
            </div>
            <div className="sm:col-span-2">
              <label className="block text-sm text-slate-600 dark:text-slate-300 mb-1">Notes</label>
              <textarea
                rows={3}
                className={`${fieldBase} h-auto py-2`}
                value={data.notes || ''}
                onChange={(e)=>setData('notes', e.target.value)}
              />
            </div>
          </div>
          <div className="flex items-center gap-3">
            <button disabled={processing} className={btnPrimary}>
              Save trip
            </button>
            <Link href={route('trips.show', trip.id)} className="text-slate-600 dark:text-slate-300 hover:underline">
              Back to Trip
            </Link>
          </div>
        </form>

        {/* Drops builder */}
        <DropsBuilder />

       
        {/* Existing consignments (improved dark-mode + formatting) */}
{consignments?.length > 0 && (
  <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4">
    <div className="text-sm text-slate-600 dark:text-slate-200 mb-2">
      Existing consignments ({consignments.length})
    </div>

    <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
      {consignments.map(c => (
        <div key={c.id} className="rounded-xl border border-gray-200 dark:border-slate-700/80 p-3 bg-white dark:bg-slate-900">
          <div className="text-[13px] leading-5">
            <div>
              <span className="text-slate-500 dark:text-slate-400">Type:</span>{' '}
              <span className="text-slate-800 dark:text-slate-100">{c.type}</span>
            </div>
            <div>
              <span className="text-slate-500 dark:text-slate-400">Status:</span>{' '}
              <span className="text-slate-800 dark:text-slate-100">{c.status}</span>
            </div>
            <div>
              <span className="text-slate-500 dark:text-slate-400">Port:</span>{' '}
              <span className="text-slate-800 dark:text-slate-100">{c.port || '—'}</span>
            </div>
            <div>
              <span className="text-slate-500 dark:text-slate-400">Vessel/Dest:</span>{' '}
              <span className="text-slate-800 dark:text-slate-100">{c.vessel}</span>
            </div>

            {Array.isArray(c.items) && c.items.length > 0 && (
              <div className="mt-2">
                <div className="text-xs text-slate-600 dark:text-slate-300 mb-0.5">Items:</div>
                <ul className="list-disc ml-5 text-[13px] text-slate-700 dark:text-slate-200">
                  {c.items.map((i, idx) => (
                    <li key={idx}>{fmtItem(i)}</li> 
                  ))}
                </ul>
              </div>
            )}
          </div>
        </div>
      ))}
    </div>
  </section>
)}

      </div>
    </AuthenticatedLayout>
  );
}
