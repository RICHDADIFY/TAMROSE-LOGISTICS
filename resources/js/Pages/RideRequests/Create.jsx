import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useForm, Link, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

function toLocalInputValue(date = new Date()) {
  const off = date.getTimezoneOffset();
  const local = new Date(date.getTime() - off * 60000);
  return local.toISOString().slice(0, 16);
}

export default function Create() {
  const { auth, meta = {}, consts = {} } = usePage().props;
  const vessels = meta.vessels ?? [];
  const guestHouses = meta.guest_houses ?? [];

  // Onne coords shared via Inertia (with a safe fallback)
  const onne = consts.onne ?? { lat: 4.723816, lng: 7.151618 };

  const { data, setData, post, processing, errors } = useForm({
    origin: 'Office',
    destination: 'Onne',
    desired_time: '',
    passengers: 1,
    purpose: '',

    // helper fields we send to backend
    destination_mode: 'free',   // 'free' | 'vessel' | 'guest'
    vessel_id: null,
    guest_id: null,

    // NEW: coords we’ll send explicitly when we know them
    to_lat: null,
    to_lng: null,
  });

  const [destMode, setDestMode] = useState('free');
  const minDateTime = useMemo(() => toLocalInputValue(new Date()), []);

  const samePlace =
    (data.origin || '').trim().toLowerCase() ===
    (data.destination || '').trim().toLowerCase();

  const presets = ['Office','Onne'];

  const pickOrigin = (v) => setData('origin', v);
  const pickDestination = (v) => setData('destination', v);

  const swapEnds = () => {
    setData('origin', data.destination);
    setData('destination', data.origin);
  };

  // Handle mode switching
  const setMode = (m) => {
    setDestMode(m);
    setData('destination_mode', m);

    if (m === 'free') {
      // clear helpers + coords
      setData('vessel_id', null);
      setData('guest_id', null);
      setData('to_lat', null);
      setData('to_lng', null);
    } else if (m === 'vessel') {
      // pre-set to Onne coords for any vessel
      setData('to_lat', onne.lat);
      setData('to_lng', onne.lng);
    } else if (m === 'guest') {
      // keep coords empty until a guest is picked
      setData('vessel_id', null);
      setData('to_lat', null);
      setData('to_lng', null);
    }
  };

  // When a vessel is chosen, display vessel name but use Onne coords
  const onSelectVessel = (id) => {
    setData('vessel_id', id || null);
    const v = vessels.find(x => String(x.id) === String(id));
    if (v) setData('destination', v.label);

    // force coords to Onne for vessels
    setData('to_lat', onne.lat);
    setData('to_lng', onne.lng);
  };

  // When a guest house is chosen, use its coords if available
  const onSelectGuest = (id) => {
    setData('guest_id', id || null);
    const g = guestHouses.find(x => String(x.id) === String(id));
    if (g) {
      setData('destination', g.label || g.name || '');
      // only set coords if provided
      if (g.lat != null && g.lng != null) {
        setData('to_lat', g.lat);
        setData('to_lng', g.lng);
      } else {
        setData('to_lat', null);
        setData('to_lng', null);
      }
    }
  };

  const submit = (e) => {
    e.preventDefault();
    if (!samePlace) post(route('ride-requests.store'));
  };

  return (
    <AuthenticatedLayout
      user={auth?.user}
      header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">New Ride Request</h2>}
    >
      <div className="p-4 md:p-6 max-w-xl mx-auto">
        <form onSubmit={submit} className="space-y-4 bg-white dark:bg-slate-900 rounded-xl shadow p-4 sm:p-6">

          {/* Origin */}
          <div>
            <label className="block text-sm font-semibold text-slate-700 dark:text-slate-200">From</label>

            <div className="flex gap-2 my-2">
              {presets.map(p => (
                <button
                  type="button"
                  key={`o-${p}`}
                  onClick={() => pickOrigin(p)}
                  className={`px-2 py-1 rounded-full text-xs border ${
                    data.origin === p
                      ? 'bg-emerald-600 text-white border-emerald-600'
                      : 'text-emerald-700 border-emerald-200'
                  }`}
                >
                  {p}
                </button>
              ))}
              <button
                type="button"
                onClick={() => setData('origin','')}
                className="ml-auto px-2 py-1 rounded-full text-xs border text-gray-600"
              >
                Clear
              </button>
            </div>

            <input
              type="text"
              value={data.origin}
              onChange={(e) => setData('origin', e.target.value)}
              placeholder="Type any location (e.g., PH Airport, Eleme, Trans-Amadi...)"
              className="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
            />
            {errors.origin && <p className="text-rose-600 text-xs mt-1">{errors.origin}</p>}
          </div>

          {/* Destination mode */}
          <div>
            <label className="block text-sm font-semibold text-slate-700 dark:text-slate-200">Destination</label>

            <div className="flex gap-2 my-2">
              {[
                { id: 'free',  label: 'Anywhere' },
                { id: 'vessel', label: 'Vessel' },
                { id: 'guest',  label: 'Guest house' },
              ].map(opt => (
                <button
                  key={opt.id}
                  type="button"
                  onClick={() => setMode(opt.id)}
                  className={`px-2 py-1 rounded-full text-xs border ${
                    destMode === opt.id
                      ? 'bg-emerald-600 text-white border-emerald-600'
                      : 'text-emerald-700 border-emerald-200'
                  }`}
                >
                  {opt.label}
                </button>
              ))}
            </div>

            {/* Vessel picker */}
            {destMode === 'vessel' && (
              <div className="mb-2">
                <select
                  value={data.vessel_id || ''}
                  onChange={(e) => onSelectVessel(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                >
                  <option value="">Select a vessel…</option>
                  {vessels.map(v => (
                    <option key={v.id} value={v.id}>{v.label}</option>
                  ))}
                </select>
                <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                  We’ll use Onne GPS for all vessels to keep ETA/distance accurate.
                </p>
              </div>
            )}

            {/* Guest house picker */}
            {destMode === 'guest' && (
              <div className="mb-2">
                <select
                  value={data.guest_id || ''}
                  onChange={(e) => onSelectGuest(e.target.value)}
                  className="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                >
                  <option value="">Select a guest house…</option>
                  {guestHouses.map(g => (
                    <option key={g.id} value={g.id}>
                      {g.label || g.name || `Guest #${g.id}`}
                    </option>
                  ))}
                </select>
                <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                  Uses the guest house’s coordinates when available.
                </p>
              </div>
            )}

            {/* Destination text (always visible) */}
            <input
              type="text"
              value={data.destination}
              onChange={(e) => setData('destination', e.target.value)}
              placeholder="Type any destination"
              className="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              readOnly={destMode !== 'free'}
            />
            {errors.destination && <p className="text-rose-600 text-xs mt-1">{errors.destination}</p>}
          </div>

          {/* Date & Time */}
          <div>
            <label className="block text-sm font-semibold text-slate-700 dark:text-slate-200">Date &amp; Time</label>
            <input
              type="datetime-local"
              value={data.desired_time}
              min={minDateTime}
              onChange={(e) => setData('desired_time', e.target.value)}
              className="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
            />
            {errors.desired_time && <p className="text-rose-600 text-xs mt-1">{errors.desired_time}</p>}
          </div>

          {/* Passengers + Purpose */}
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="block text-sm font-semibold text-slate-700 dark:text-slate-200">Passengers</label>
              <input
                type="number"
                min="1"
                max="14"
                value={data.passengers}
                onChange={(e) => setData('passengers', e.target.value)}
                className="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              />
              <p className="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Max 14 (Hiace capacity)</p>
              {errors.passengers && <p className="text-rose-600 text-xs mt-1">{errors.passengers}</p>}
            </div>

            <div>
              <label className="block text-sm font-semibold text-slate-700 dark:text-slate-200">Purpose (optional)</label>
              <input
                type="text"
                maxLength="180"
                value={data.purpose}
                onChange={(e) => setData('purpose', e.target.value)}
                className="w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
              />
              {errors.purpose && <p className="text-rose-600 text-xs mt-1">{errors.purpose}</p>}
            </div>
          </div>

          {/* Hidden coords (sent with the form) */}
          <input type="hidden" name="to_lat" value={data.to_lat ?? ''} />
          <input type="hidden" name="to_lng" value={data.to_lng ?? ''} />

          {/* Actions */}
          <div className="flex items-center justify-between gap-3 pt-2">
            <Link href={route('ride-requests.index')} className="text-slate-600 dark:text-slate-300 text-sm">Back</Link>
            <button
              disabled={processing || samePlace}
              className="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:opacity-90 disabled:opacity-50"
            >
              {processing ? 'Submitting…' : 'Submit'}
            </button>
          </div>

          {samePlace && (
            <div className="rounded-md bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-200 text-xs px-3 py-2">
              Origin and destination can’t be the same.
            </div>
          )}
        </form>
      </div>
    </AuthenticatedLayout>
  );
}
