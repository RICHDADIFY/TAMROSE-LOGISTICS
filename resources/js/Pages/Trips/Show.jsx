// resources/js/Pages/Trips/Show.jsx
import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ContactPanel from '@/Components/ContactPanel';
import LiveTripMap from '@/Components/LiveTripMap';
import RouteMap from '@/Components/RouteMap';
import DeliverModal from '@/Components/DeliverModal';
import ConsignmentTimeline from '@/Components/ConsignmentTimeline';
import StatusPill from '@/Components/StatusPill';
import { postForm } from '@/lib/http';




// ---------- Local helper component (NO export here) ----------
// ---------- Local helper component (replace your existing one) ----------
function ConsignmentCard({ consignment, isDriver, isManager }) {
  const [showDeliver, setShowDeliver]   = React.useState(false);
  const [showTimeline, setShowTimeline] = React.useState(false);
  const [otpMsg, setOtpMsg]             = React.useState(null);
  const [otpLoading, setOtpLoading]     = React.useState(false);

  // inside ConsignmentCard(...)
const [requireOtp, setRequireOtp] = React.useState(!!consignment.require_otp);
const [toggling, setToggling] = React.useState(false);


  const lastType = consignment?.last_event?.type || consignment?.latestEvent?.type;
  const destLabel =
    consignment.destination_label ??
    (consignment.vessel ? `${consignment.port ?? ''} • ${consignment.vessel}` : consignment.destination ?? consignment.type ?? 'Consignment');

  async function generateOtp() {
  try {
    setOtpMsg(null); setOtpLoading(true);
    const fd = new FormData();
    const res = await postForm(`/consignments/${consignment.id}/prepare-delivery`, fd);
    const txt = await res.text();
    let data = null; try { data = JSON.parse(txt); } catch {}
    if (!res.ok || !data || data.ok !== true) {
      const snippet = (txt || '').slice(0, 140);
      setOtpMsg(`Failed (${res.status}). ${data?.error || snippet || 'No JSON'}`);
      return;
    }
    const expires = data.expires_at ? new Date(data.expires_at).toLocaleTimeString() : null;
    setOtpMsg(data.otp ? `OTP: ${data.otp}${expires ? ` (expires ${expires})` : ''}` : 'OTP created.');
  } finally {
    setOtpLoading(false);
  }
}


  async function toggleRequireOtp(e) {
  const next = e.target.checked;
  setToggling(true);
  setOtpMsg(null);
  try {
    const fd = new FormData();
    fd.append('require_otp', next ? '1' : '0');

    // uses your CSRF-safe helper
    const res = await postForm(`/consignments/${consignment.id}/require-otp`, fd);
    const txt = await res.text();
    let data = null; try { data = JSON.parse(txt); } catch {}

    if (!res.ok || !data?.ok) {
      throw new Error((data?.error || txt || '').slice(0, 160) || 'Failed');
    }

    setRequireOtp(!!data.require_otp);
  } catch (err) {
    setOtpMsg(`Toggle failed: ${err.message}`);
    // revert UI switch if needed
    setRequireOtp(prev => prev);
  } finally {
    setToggling(false);
  }
}


  async function copyOtpFromMsg() {
    const m = (otpMsg || '').match(/\b\d{3,}\b/);
    if (!m) return;
    try { await navigator.clipboard?.writeText(m[0]); setOtpMsg(`Copied OTP: ${m[0]}`); } catch {}
  }

  return (
    <div className="rounded-2xl border bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700/70 p-4 shadow-sm">
      <div className="flex items-start justify-between gap-3">
        <div>
          <h3 className="font-semibold text-slate-900 dark:text-slate-100">{destLabel}</h3>
          {!!consignment.items_text && (
            <div className="text-sm text-slate-600 dark:text-slate-300">{consignment.items_text}</div>
          )}
        </div>
        {lastType && <StatusPill type={lastType} />}
      </div>

      <div className="mt-3 flex flex-col gap-1">
        <div className="flex flex-wrap gap-2">
          {isDriver && (
            <button
              onClick={() => setShowDeliver(true)}
              className="h-8 px-3 rounded-lg bg-emerald-600 text-white text-sm hover:bg-emerald-700"
              type="button"
            >
              Deliver
            </button>
          )}

          {isManager && (
              <>
                <button
                  onClick={() => setShowTimeline(t => !t)}
                  className="h-8 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-sm text-slate-800 dark:text-slate-100"
                  type="button"
                >
                  {showTimeline ? 'Hide Timeline' : 'View Timeline'}
                </button>

                {/* 👇 OTP required toggle */}
                <label className="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                  <input
                    type="checkbox"
                    className="h-4 w-4 accent-emerald-600"
                    checked={requireOtp}
                    onChange={toggleRequireOtp}
                    disabled={toggling}
                  />
                  OTP required
                </label>

                {/* 👇 Only show Generate OTP when toggle is on */}
                {requireOtp && (
                  <button
                    onClick={generateOtp}
                    disabled={otpLoading}
                    className="h-8 px-3 rounded-lg bg-violet-600 text-white text-sm hover:bg-violet-700 disabled:opacity-60"
                    type="button"
                  >
                    {otpLoading ? 'Generating…' : 'Generate OTP'}
                  </button>
                )}
              </>
            )}

        </div>

        {isManager && otpMsg && (
          <div className="inline-flex items-center gap-2 text-xs mt-1 px-2 py-1 rounded bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-200">
            <span>{otpMsg}</span>
            {/\b\d{3,}\b/.test(otpMsg) && (
              <button
                type="button"
                onClick={copyOtpFromMsg}
                className="px-2 py-0.5 rounded bg-violet-600 text-white hover:bg-violet-700"
                title="Copy OTP"
              >
                Copy
              </button>
            )}
          </div>
        )}
      </div>

      {showDeliver && (
          <DeliverModal
            consignment={{ ...consignment, require_otp: requireOtp }}  // 👈 pass the current toggle state
            onClose={(success) => {
              setShowDeliver(false);
              if (success) router.reload({ only: ['trip'] });
            }}
          />
        )}

      {showTimeline && isManager && (
        <div className="mt-4">
          <ConsignmentTimeline consignmentId={consignment.id} auto intervalMs={10000} />
        </div>
      )}
    </div>
  );
}





function fmtItem(i) {
  const desc = (i.description ?? '').trim();
  const qty  = (i.quantity ?? '').toString().trim();
  const unit = (i.unit ?? '').toString().trim();
  const numericUnit = /^[0-9.]+$/.test(unit);
  const unitLabel = unit ? (numericUnit ? ' units' : ` ${unit}`) : '';
  return `${desc} × ${qty}${unitLabel}`;
}



/* ---------- Presentational helpers ---------- */
function StatusBadge({ status }) {
  const tone = {
    scheduled:   'bg-slate-100 text-slate-700 dark:bg-slate-800/60 dark:text-slate-200',
    dispatched:  'bg-sky-100 text-sky-700 dark:bg-sky-800/60 dark:text-sky-200',
    'in-progress':'bg-amber-100 text-amber-800 dark:bg-amber-800/60 dark:text-amber-100',
    completed:   'bg-emerald-100 text-emerald-700 dark:bg-emerald-800/60 dark:text-emerald-100',
    cancelled:   'bg-rose-100 text-rose-700 dark:bg-rose-800/60 dark:text-rose-200',
  }[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800/60 dark:text-slate-200';
  return <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${tone}`}>{status}</span>;
}

function FactRow({ label, children }) {
  return (
    <div className="grid grid-cols-12 gap-3 py-2 border-b border-gray-100 dark:border-slate-800 last:border-none">
      <div className="col-span-4 sm:col-span-3 text-[13px] leading-5 text-slate-500 dark:text-slate-400">{label}</div>
      <div className="col-span-8 sm:col-span-9 text-[14px] leading-6 text-slate-800 dark:text-slate-100">{children}</div>
    </div>
  );
}

/* ---------- DriverCard (visual only) ---------- */
function DriverCard() {
  const { driver, auth } = usePage().props;
  const roles = new Set((auth?.user?.roles || []).map(String));
  const isDriver = roles.has('Driver') || !!auth?.user?.is_driver;
  if (isDriver || !driver) return null;

  const avatar =
    driver.avatar_url ||
    `https://ui-avatars.com/api/?name=${encodeURIComponent(driver.name)}&background=10B981&color=fff`;
  const wa = driver.phone ? `https://wa.me/${String(driver.phone).replace(/\D/g, '')}` : '#';

  return (
    <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4">
      <div className="flex items-center gap-3">
        <img
          src={avatar}
          alt={driver.name}
          className="h-14 w-14 rounded-full object-cover ring-2 ring-emerald-500/20"
        />
        <div className="min-w-0">
          <div className="text-xs text-slate-500 dark:text-slate-400">Assigned driver</div>
          <div className="font-semibold text-slate-800 dark:text-slate-100 truncate">{driver.name}</div>
          {driver.phone && (
            <a href={`tel:${driver.phone}`} className="text-sm text-emerald-700 dark:text-emerald-300 hover:underline">
              {driver.phone}
            </a>
          )}
        </div>
        <div className="ml-auto text-right">
          <div className="text-xs text-slate-500 dark:text-slate-400">Trips completed</div>
          <div className="text-xl font-bold text-slate-900 dark:text-white">{driver.completed_trips ?? 0}</div>
        </div>
      </div>

      <div className="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div className="rounded-xl border border-gray-100 dark:border-slate-800 p-3">
          <div className="text-xs text-slate-500 dark:text-slate-400">Vehicle</div>
          <div className="font-medium text-slate-800 dark:text-slate-100">
            {driver?.vehicle?.label || '—'}
          </div>
          {driver?.vehicle?.plate && (
            <div className="text-xs text-slate-500 dark:text-slate-400">{driver.vehicle.plate}</div>
          )}
        </div>

        <div className="flex gap-2 sm:col-span-2">
          {driver.phone ? (
            <>
              <a
                href={`tel:${driver.phone}`}
                className="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 flex-1"
              >
                Call
              </a>
              <a
                href={`sms:${driver.phone}`}
                className="inline-flex items-center justify-center h-9 px-3 rounded-lg border bg-white dark:bg-slate-900 border-gray-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium flex-1"
              >
                SMS
              </a>
              <a
                href={wa}
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center justify-center h-9 px-3 rounded-lg bg-emerald-700 text-white text-sm font-medium hover:bg-emerald-800 flex-1"
              >
                WhatsApp
              </a>
            </>
          ) : (
            <div className="text-sm text-slate-500 dark:text-slate-400">No contact number on file.</div>
          )}
        </div>
      </div>
    </section>
  );
}

/* ---------- Page ---------- */
export default function Show({ trip }) {
  const { auth, contacts = [], staff_contacts = [], existing_consignments = [], show_maps, map = {}, config = {} } = usePage().props;
  // Robust role checks (supports both role names and booleans in props)
  const roles = new Set((auth?.user?.roles || []).map(String));
  const isManager = roles.has('Logistics Manager') || roles.has('Super Admin') || !!auth?.user?.is_manager;
  const isDriver  = roles.has('Driver') || !!auth?.user?.is_driver;
  const isStaff   = !isManager && !isDriver;

  // ✅ Only show maps for Logistics Manager, and only if backend allowed it
  const showMaps = Boolean(show_maps && isManager);

  const [summary, setSummary] = useState(null);

  const from = (map?.from?.lat && map?.from?.lng)
    ? { lat: Number(map.from.lat), lng: Number(map.from.lng) }
    : null;

  const to = (map?.to?.lat && map?.to?.lng)
    ? { lat: Number(map.to.lat), lng: Number(map.to.lng) }
    : null;

  return (
    <AuthenticatedLayout header={<h2 className="text-2xl font-bold text-slate-900 dark:text-white">Trip #{trip?.id}</h2>}>
      <Head title={`Trip #${trip?.id}`} />

      <div className="p-6 space-y-6 bg-gray-50 dark:bg-slate-950 min-h-screen">
        {/* Trip facts */}
        <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800">
          <div className="p-4 sm:p-5">
            <div className="mb-3 flex items-center justify-between">
              <div className="text-sm text-slate-500 dark:text-slate-400">Overview</div>
              <StatusBadge status={trip?.status} />
            </div>

            <FactRow label="Vehicle">
              {trip?.vehicle?.label || trip?.vehicle?.plate_number || `Vehicle #${trip?.vehicle?.id ?? '—'}`}
            </FactRow>
            <FactRow label="Driver">{trip?.driver?.name ?? '—'}</FactRow>
            <FactRow label="Direction">{trip?.direction ?? '—'}</FactRow>
            <FactRow label="Origin">{trip?.origin ?? '—'}</FactRow>
            <FactRow label="Destination">{trip?.destination ?? '—'}</FactRow>
            <FactRow label="Depart">{trip?.depart_at ?? '—'}</FactRow>
            <FactRow label="Return">{trip?.return_at ?? '—'}</FactRow>
            <FactRow label="Staff on trip">
              <span className="inline-flex items-center gap-2">
                <span className="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200 text-xs">
                  {trip?.staff_count ?? staff_contacts.length ?? 0}
                </span>
                <span className="text-slate-600 dark:text-slate-300 text-sm">assigned</span>
              </span>
            </FactRow>
            <FactRow label="Notes">{trip?.notes ?? '—'}</FactRow>
          </div>

          {/* Driver-only: quick navigation without embedded maps */}
          {isDriver && (to || trip?.destination) && (
            <div className="px-4 pb-4 sm:px-5">
              <a
                href={
                  to
                    ? `https://www.google.com/maps/dir/?api=1&destination=${to.lat},${to.lng}`
                    : `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(trip.destination)}`
                }
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center justify-center h-9 px-4 rounded-lg text-sm font-medium border bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-200 border-emerald-200 dark:border-emerald-800"
              >
                Open in Google Maps
              </a>
            </div>
          )}
        </section>

        {/* Manager: Edit button */}
        {isManager && (
          <div className="flex justify-end">
            <Link
              href={route('trips.edit', trip.id)}
              className="inline-flex items-center justify-center h-9 px-4 rounded-lg bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700"
            >
              Edit Trip
            </Link>
          </div>
        )}

        {/* Route map — managers only, needs BOTH from & to */}
        {showMaps && from && to && (
          <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-3">
            <div className="text-sm text-slate-700 dark:text-slate-300 mb-2">Route &amp; ETA</div>
            <RouteMap
              from={from}
              to={to}
              onSummary={setSummary}
              apiKey={config?.google?.mapsBrowserKey}
              summaryUrl={route('api.directions.summary')}
            />
            {summary && (
              <div className="mt-2 text-slate-700 dark:text-slate-300">
                <div className="inline-flex items-center gap-2 sm:gap-3 text-xs sm:text-sm whitespace-nowrap">
                  <span><span className="font-medium">Distance:</span> {summary.distance_text}</span>
                  <span className="opacity-50">•</span>
                  <span><span className="font-medium">ETA:</span> {summary.duration_text}</span>
                  {summary.duration_in_traffic_s && (
                    <>
                      <span className="opacity-50">•</span>
                      <span className="text-xs text-slate-500 dark:text-slate-400">traffic-aware</span>
                    </>
                  )}
                </div>
              </div>
            )}
          </section>
        )}

        {/* Live tracking — managers only, needs destination */}
        {showMaps && to && (
          <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-3">
            <div className="text-sm text-slate-700 dark:text-slate-300 mb-2">Live tracking</div>
            <LiveTripMap
              tripId={trip.id}
              toLat={to.lat}
              toLng={to.lng}
              pollMs={10000}
              windowMins={180}
            />
          </section>
        )}

        {/* Driver view: Logistics Manager + Assigned Staff */}
        {Boolean(isDriver && contacts?.length > 0) && (
          <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4">
            <div className="text-sm text-slate-500 dark:text-slate-400 mb-2">Contacts</div>
            <ContactPanel contacts={contacts} />
          </section>
        )}

        {Boolean(isDriver && staff_contacts?.length > 0) && (
          <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4">
            <div className="text-sm text-slate-500 dark:text-slate-400 mb-2">Assigned Staff</div>
            <ContactPanel contacts={staff_contacts} />
          </section>
        )}

        {/* Manager view: assigned staff list */}
        {Boolean(isManager && staff_contacts?.length > 0) && (
          <section className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-100 dark:border-slate-800 p-4">
            <div className="text-sm text-slate-500 dark:text-slate-400 mb-2">
              Assigned Staff ({trip?.staff_count ?? staff_contacts.length})
            </div>
            <ContactPanel contacts={staff_contacts} />
          </section>
        )}

      {/* Consignments (interactive) */}
{(Array.isArray(trip?.consignments) ? trip.consignments.length : existing_consignments.length) > 0 && (
  <section className="bg-transparent p-0">
    <div className="text-sm text-slate-600 dark:text-slate-200 mb-2">
      Consignments ({Array.isArray(trip?.consignments) ? trip.consignments.length : existing_consignments.length})
    </div>

    <div className="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
      {(Array.isArray(trip?.consignments) ? trip.consignments : existing_consignments).map((c) => (
        <ConsignmentCard
          key={c.id}
          consignment={{
            ...c,
            require_otp: !!c.require_otp, // 👈 ensure boolean
            destination_label:
              c.destination_label ??
              (c.vessel ? `${c.port ?? ''} • ${c.vessel}` : c.destination ?? c.type ?? 'Consignment'),
            items_text:
              c.items_text ??
              (Array.isArray(c.items) ? c.items.map(fmtItem).join(', ') : ''),
            trip_id: c.trip_id ?? trip.id,
          }}
          isDriver={isDriver}
          isManager={isManager}
        />
      ))}
    </div>
  </section>
)}



        {/* Manager view: driver card */}
        {!isDriver && <DriverCard />}

        {/* Footer link for non-managers */}
        {!isManager && (
          <div className="flex">
            <Link href={route('trips.index')} className="text-slate-600 dark:text-slate-300 hover:underline">
              Back to Trips
            </Link>
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
