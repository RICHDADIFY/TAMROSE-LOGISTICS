import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import ContactPanel from '@/Components/ContactPanel';
import { useState } from 'react';
import RouteMap from '@/Components/RouteMap';




function StatusChip({ status }) {
  const cls = {
    pending:   'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
    approved:  'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-200',
    rejected:  'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
    cancelled: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200',
    assigned:  'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200',
    completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
  }[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200';

  return <span className={`px-2 py-1 text-xs rounded-full capitalize ${cls}`}>{status}</span>;
}

export default function Show() {
  const { auth, request, flash, can = {}, driver, contacts = [], map = {}, config = {} } = usePage().props;

  const [summary, setSummary] = useState(null);

  // Derive coords (no useMemo needed; RouteMap de-dupes internally)
  const from = (map?.from?.lat && map?.from?.lng)
    ? { lat: Number(map.from.lat), lng: Number(map.from.lng) }
    : null;

  const to = (map?.to?.lat && map?.to?.lng)
    ? { lat: Number(map.to.lat), lng: Number(map.to.lng) }
    : null;

  const isOwner   = auth?.user?.id === request.user_id;
  const isManager = !!auth?.user?.is_manager;
  const isPending = request.status === 'pending';

  const canApprove = can.approve ?? (isManager && isPending);
  const canReject  = can.reject  ?? (isManager && isPending);
  const canCancel  = can.cancel  ?? (isOwner && isPending);

  const doApprove = () => {
    if (!confirm('Approve this request?')) return;
    router.post(route('ride-requests.approve', request.id), {}, { preserveScroll: true });
  };
  const doReject = () => {
    const reason = prompt('Optional: enter a reason for rejection') || '';
    router.post(route('ride-requests.reject', request.id), { note: reason }, { preserveScroll: true });
  };
  const doCancel = () => {
    if (!confirm('Cancel this request?')) return;
    const note = prompt('Optional: add a note') || '';
    router.post(route('ride-requests.cancel', request.id), { note }, { preserveScroll: true });
  };

  const avatar = driver?.avatar_url
    ?? (driver?.name ? `https://ui-avatars.com/api/?name=${encodeURIComponent(driver.name)}&background=10B981&color=fff` : null);

  const formatLocal = (iso) => (iso ? new Date(iso).toLocaleString() : '—');
  const waLink = driver?.phone ? `https://wa.me/${String(driver.phone).replace(/\D/g, '')}` : '#';

  return (
    <AuthenticatedLayout
      user={auth?.user}
      header={
        <div className="flex items-center justify-between">
          <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">Ride Request</h2>
          <StatusChip status={request.status} />
        </div>
      }
    >
      <Head title={`Ride Request #${request.id}`} />

      <div className="p-4 sm:p-6 max-w-3xl mx-auto">
        {flash?.success && (
          <div className="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-200 px-3 py-2">
            {flash.success}
          </div>
        )}
        {flash?.error && (
          <div className="mb-4 rounded-lg bg-rose-50 dark:bg-rose-900/30 text-rose-700 dark:text-rose-200 px-3 py-2">
            {flash.error}
          </div>
        )}

        {/* Request details card */}
        <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 shadow-sm">
          <div className="p-4 sm:p-6 space-y-3 text-sm">
            <div className="flex justify-between">
              <div className="text-gray-600 dark:text-gray-300">Route</div>
              <div className="text-gray-900 dark:text-gray-100 font-medium">
                {request.origin} → {request.destination}
              </div>
            </div>

            <div className="flex justify-between">
              <div className="text-gray-600 dark:text-gray-300">Desired time</div>
              <div className="text-gray-900 dark:text-gray-100 font-medium">
                {formatLocal(request.desired_time)}
              </div>
            </div>

            <div className="flex justify-between">
              <div className="text-gray-600 dark:text-gray-300">Passengers</div>
              <div className="text-gray-900 dark:text-gray-100 font-medium">
                {request.passengers ?? '—'}
              </div>
            </div>

            {request.purpose && (
              <div>
                <div className="text-gray-600 dark:text-gray-300">Purpose</div>
                <div className="text-gray-900 dark:text-gray-100">{request.purpose}</div>
              </div>
            )}

            {request.manager_note && (
              <div>
                <div className="text-gray-600 dark:text-gray-300">Manager note</div>
                <div className="text-gray-900 dark:text-gray-100">{request.manager_note}</div>
              </div>
            )}
          </div>

          <div className="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between gap-2">
            <Link href={route('ride-requests.index')} className="text-sm text-gray-600 dark:text-gray-300 hover:underline">
              ← Back to My Requests
            </Link>

            <div className="flex items-center gap-2">
              {canApprove && (
                <button onClick={doApprove} className="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm hover:opacity-90">
                  Approve
                </button>
              )}
              {canReject && (
                <button onClick={doReject} className="px-3 py-2 rounded-lg bg-amber-600 text-white text-sm hover:opacity-90">
                  Reject
                </button>
              )}
              {canCancel && (
                <button onClick={doCancel} className="px-3 py-2 rounded-lg bg-rose-600 text-white text-sm hover:opacity-90">
                  Cancel
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Directions (route line + ETA) */}
        {from && to && (
          <section className="mt-4">
            <div className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-200 dark:border-gray-700 p-3">
              <div className="text-sm text-gray-700 dark:text-gray-300 mb-2">Route &amp; ETA</div>

              {/*<div className="text-xs text-gray-500 mb-1">
                FROM: {from?.lat}, {from?.lng} — TO: {to?.lat}, {to?.lng}
              </div>*/}

              <RouteMap
              from={from}
              to={to}
              onSummary={setSummary}
              apiKey={config?.google?.mapsBrowserKey}
              summaryUrl={route('api.directions.summary')}
            />


              {summary && (
                <div className="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-300 mt-2">
                  <div><span className="font-medium">Distance:</span> {summary.distance_text}</div>
                  <span className="opacity-50">•</span>
                  <div><span className="font-medium">ETA:</span> {summary.duration_text}</div>
                  {summary.duration_in_traffic_s && (
                    <>
                      <span className="opacity-50">•</span>
                      <div className="text-xs text-gray-500 dark:text-gray-400">traffic-aware</div>
                    </>
                  )}
                </div>
              )}
            </div>
          </section>
        )}

        {/* Contacts */}
        {contacts?.length > 0 && (
          <section className="mt-4">
            <div className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-200 dark:border-gray-700 p-4 sm:p-5">
              <div className="text-sm text-gray-500 dark:text-gray-400 mb-3">Contacts</div>
              <ContactPanel contacts={contacts} />
            </div>
          </section>
        )}

        {/* Driver */}
        {driver && (
          <section className="mt-4">
            <div className="bg-white dark:bg-slate-900 rounded-2xl shadow border border-gray-200 dark:border-gray-700 p-4 sm:p-5">
              <div className="flex items-center gap-3">
                {avatar ? (
                  <img src={avatar} alt={driver.name} className="h-14 w-14 rounded-full object-cover ring-2 ring-emerald-500/20" />
                ) : (
                  <div className="h-14 w-14 rounded-full bg-emerald-100 dark:bg-emerald-900/30 ring-2 ring-emerald-500/20" />
                )}
                <div className="min-w-0">
                  <div className="text-xs text-gray-500 dark:text-gray-400">Your driver</div>
                  <div className="font-semibold text-gray-900 dark:text-gray-100 truncate">{driver.name}</div>
                  {driver.phone && (
                    <a href={`tel:${driver.phone}`} className="text-sm text-emerald-700 dark:text-emerald-300 hover:underline">
                      {driver.phone}
                    </a>
                  )}
                </div>
                <div className="ml-auto text-right">
                  <div className="text-xs text-gray-500 dark:text-gray-400">Trips completed</div>
                  <div className="text-xl font-bold text-gray-900 dark:text-gray-100">{driver.completed_trips ?? 0}</div>
                </div>
              </div>

              <div className="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div className="rounded-xl border border-gray-100 dark:border-gray-700 p-3">
                  <div className="text-xs text-gray-500 dark:text-gray-400">Vehicle</div>
                  <div className="font-medium text-gray-900 dark:text-gray-100">
                    {driver?.vehicle?.label || '—'}
                  </div>
                  {driver?.vehicle?.plate && (
                    <div className="text-xs text-gray-500 dark:text-gray-400">{driver.vehicle.plate}</div>
                  )}
                </div>

                <div className="flex gap-2 sm:col-span-2">
                  {driver.phone ? (
                    <>
                      <a href={`tel:${driver.phone}`} className="flex-1 px-3 py-2 rounded-xl bg-emerald-600 text-white text-center">
                        Call
                      </a>
                      <a href={`sms:${driver.phone}`} className="flex-1 px-3 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-center border border-emerald-200 dark:bg-transparent dark:border-emerald-700 dark:text-emerald-300">
                        SMS
                      </a>
                      <a href={`https://wa.me/${String(driver.phone).replace(/\D/g, '')}`} target="_blank" rel="noreferrer" className="flex-1 px-3 py-2 rounded-xl bg-green-600 text-white text-center">
                        WhatsApp
                      </a>
                    </>
                  ) : (
                    <div className="text-sm text-gray-500 dark:text-gray-400">No contact number on file.</div>
                  )}
                </div>
              </div>
            </div>
          </section>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
