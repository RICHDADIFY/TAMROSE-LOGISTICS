import { useEffect, useRef, useState } from "react";
import { Head, usePage, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";

import RouteSummaryPill from '@/Components/RouteSummaryPill';
import { useBatchSummaries } from '@/Components/useBatchSummaries';

function prettyDt(dt) {
  try { return new Date(dt).toLocaleString(); } catch { return dt || "—"; }
}
function ageFrom(iso) {
  if (!iso) return "—";
  const s = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
  if (s < 60) return `${s}s ago`;
  const m = Math.floor(s/60); if (m < 60) return `${m}m ago`;
  const h = Math.floor(m/60); if (h < 24) return `${h}h ago`;
  const d = Math.floor(h/24); return `${d}d ago`;
}


function AssignForm({ request, vehicles, drivers, activeTrips = [], onDone }) {
  // ✅ Only get errors from page props
  const { errors = {} } = usePage().props;

  const [vehicleId, setVehicleId] = useState("");
  const [driverId, setDriverId] = useState("");
  const [departAt, setDepartAt] = useState(request.desired_input || "");
  const [returnAt, setReturnAt] = useState("");
  const [notes, setNotes] = useState("");
  const [attachTripId, setAttachTripId] = useState("");

  const submit = (e) => {
    e.preventDefault();
    router.post(
      route("dispatch.assign", request.id),
      {
        vehicle_id: vehicleId,
        driver_id:  driverId,
        depart_at:  departAt || null,
        return_at:  returnAt || null,
        notes:      notes || null,
      },
      {
        preserveScroll: true,
        preserveState: true,
        only: ["pending","ready","drivers","vehicles","active_trips"],
        onSuccess: onDone,
      }
    );
  };

  const attach = (e) => {
    e.preventDefault();
    if (!attachTripId) return;
    router.post(
      route("dispatch.attach", [request.id, attachTripId]),
      {},
      {
        preserveScroll: true,
        preserveState: true,
        only: ["pending","ready","drivers","vehicles","active_trips"],
        onSuccess: onDone,
      }
    );
  };

  const errClass = (name) =>
    errors[name] ? "border-red-500 focus:ring-red-500" : "border-gray-300 focus:ring-blue-500";

  return (
    <form onSubmit={submit} className="grid grid-cols-1 sm:grid-cols-5 gap-2">
      {/* Vehicle */}
      <div className="sm:col-span-1">
        <select
          name="vehicle_id"
          value={vehicleId}
          onChange={e=>setVehicleId(e.target.value)}
          className={`w-full rounded p-2 border ${errClass('vehicle_id')}`}
          aria-invalid={Boolean(errors.vehicle_id) || undefined}
        >
          <option value="">Select vehicle…</option>
          {vehicles.map(v => <option key={v.id} value={v.id}>{v.label}</option>)}
        </select>
        {errors.vehicle_id && <p className="mt-1 text-xs text-red-600">{errors.vehicle_id}</p>}
      </div>

      {/* Driver */}
      <div className="sm:col-span-1">
        <select
          name="driver_id"
          value={driverId}
          onChange={e=>setDriverId(e.target.value)}
          className={`w-full rounded p-2 border ${errClass('driver_id')}`}
          aria-invalid={Boolean(errors.driver_id) || undefined}
        >
          <option value="">Select driver…</option>
          {drivers.map(d => <option key={d.id} value={d.id}>{d.name}{d.phone ? ` (${d.phone})` : ""}</option>)}
        </select>
        {errors.driver_id && <p className="mt-1 text-xs text-red-600">{errors.driver_id}</p>}
      </div>

      {/* Depart */}
      <div className="sm:col-span-1">
        <input
          type="datetime-local"
          name="depart_at"
          value={departAt || ""}
          onChange={e=>setDepartAt(e.target.value)}
          className={`w-full rounded p-2 border ${errClass('depart_at')}`}
          aria-invalid={Boolean(errors.depart_at) || undefined}
        />
        {errors.depart_at && <p className="mt-1 text-xs text-red-600">{errors.depart_at}</p>}
      </div>

      {/* Return */}
      <div className="sm:col-span-1">
        <input
          type="datetime-local"
          name="return_at"
          value={returnAt || ""}
          onChange={e=>setReturnAt(e.target.value)}
          className={`w-full rounded p-2 border ${errClass('return_at')}`}
          aria-invalid={Boolean(errors.return_at) || undefined}
        />
        {errors.return_at && <p className="mt-1 text-xs text-red-600">{errors.return_at}</p>}
      </div>

      {/* Notes */}
      <div className="sm:col-span-5">
        <input
          type="text"
          name="notes"
          value={notes}
          onChange={e=>setNotes(e.target.value)}
          placeholder="Notes (optional)"
          className={`w-full rounded p-2 border ${errClass('notes')}`}
          aria-invalid={Boolean(errors.notes) || undefined}
        />
        {errors.notes && <p className="mt-1 text-xs text-red-600">{errors.notes}</p>}
      </div>

      {/* General errors */}
      {(errors.request || errors.schedule || errors.capacity) && (
        <div className="sm:col-span-5 rounded-md bg-red-50 p-2 text-sm text-red-700">
          {errors.request && <div>{errors.request}</div>}
          {errors.schedule && <div>{errors.schedule}</div>}
          {errors.capacity && <div>{errors.capacity}</div>}
        </div>
      )}

      {/* Actions */}
      <div className="sm:col-span-5 flex flex-col sm:flex-row gap-2 justify-end">
        <button type="submit" className="px-4 py-2 rounded bg-blue-600 text-white">
          Assign (create new trip)
        </button>

        <div className="flex gap-2 items-center">
          <select
            value={attachTripId}
            onChange={e => setAttachTripId(e.target.value)}
            className="rounded border p-2 w-64"
          >
            <option value="">Attach to existing trip…</option>
            {activeTrips.map(t => (
              <option key={t.id} value={t.id}>
                {t.label}
              </option>
            ))}
          </select>
          <button onClick={attach} className="px-4 py-2 rounded bg-emerald-600 text-white">
            Attach
          </button>
        </div>
      </div>
    </form>
  );
}




function RequestCard({ r, mode, onApprove, onReject, expanded, onExpand, vehicles, drivers, summary, activeTrips = [] }) {
  const ref = useRef(null);
  useEffect(() => { if (expanded && ref.current) ref.current.scrollIntoView({ behavior: "smooth", block: "center" }); }, [expanded]);

  const hasCoords = r.from_lat && r.from_lng && r.to_lat && r.to_lng;

  return (
    <div className={`rounded-xl border shadow-sm bg-white dark:bg-slate-900 dark:border-gray-700 p-3 mb-3 ${expanded ? "ring-2 ring-emerald-500/30" : ""}`}>
      <div className="flex items-start gap-3">
        <div className="flex-1 min-w-0">
          <div className="flex items-center justify-between">
            <div className="font-semibold text-gray-900 dark:text-gray-100">
              RR-{r.id} <span className="ml-2 text-xs text-gray-500">by {r.requested_by || "—"}</span>
            </div>
            <div className="text-xs text-gray-500">{ageFrom(r.created_at)}</div>
          </div>

          <div className="text-sm text-gray-600 dark:text-gray-300 mt-1">
            <div><span className="text-gray-500">Route:</span> {r.origin} → {r.destination}</div>
            <div><span className="text-gray-500">When:</span> {prettyDt(r.desired_time)}</div>
            <div><span className="text-gray-500">Passengers:</span> {r.passengers ?? "—"}</div>
            {r.purpose && <div className="truncate"><span className="text-gray-500">Purpose:</span> {r.purpose}</div>}

            {/* Distance • ETA pill */}
            {hasCoords ? (
              <div className="mt-2">
                <RouteSummaryPill summary={summary} />
              </div>
            ) : (
              <div className="mt-2 text-xs text-gray-400">Add origin/destination to show Distance • ETA</div>
            )}
          </div>
        </div>

        {mode === "pending" && (
          <div className="flex flex-col gap-2 ml-2">
            <button onClick={() => onApprove(r.id)} className="px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-sm hover:opacity-90">Approve</button>
            <button onClick={() => onReject(r.id)}  className="px-3 py-1.5 rounded-lg bg-rose-600 text-white text-sm hover:opacity-90">Reject</button>
          </div>
        )}

        {mode === "ready" && !expanded && (
          <div className="ml-2">
            <button onClick={() => onExpand(r.id)} className="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm hover:opacity-90">Assign</button>
          </div>
        )}
      </div>

      {mode === "ready" && expanded && (
        <div ref={ref} className="mt-3 border-t pt-3 dark:border-gray-700">
          <AssignForm
            request={r}
            vehicles={vehicles}
            drivers={drivers}
            activeTrips={activeTrips}
            onDone={() => onExpand(null)}
          />
        </div>
      )}
    </div>
  );
}

export default function DispatchIndex() {
 const { auth, pending = [], ready = [], vehicles = [], drivers = [], active_trips = [] } = usePage().props;
  const [expandedId, setExpandedId] = useState(null);
  const [justApproved, setJustApproved] = useState(null);

  // Build items for batch call (both columns)
  const all = [...pending, ...ready];
  const items = all
    .filter(r => r.from_lat && r.from_lng && r.to_lat && r.to_lng)
    .map(r => ({
      key: `req-${r.id}`,
      from: { lat: Number(r.from_lat), lng: Number(r.from_lng) },
      to:   { lat: Number(r.to_lat),   lng: Number(r.to_lng) },
    }));

  // One POST -> summaries for all visible cards
  const summaries = useBatchSummaries(items, route('api.directions.batch'));

  const onApprove = (id) => {
    if (!confirm("Approve this request?")) return;
    router.post(route("ride-requests.approve", id), {}, {
      preserveScroll: true,
      only: ["pending","ready"], // quick partial reload
      onSuccess: () => setJustApproved(id),
    });
  };

  const onReject = (id) => {
    const note = prompt("Optional: reason for rejection") || "";
    router.post(route("ride-requests.reject", id), { note }, { preserveScroll: true, only: ["pending","ready"] });
  };

  // After approving, auto-expand it in Ready pane
  useEffect(() => {
    if (!justApproved) return;
    const exists = ready.some(r => r.id === justApproved);
    if (exists) {
      setExpandedId(justApproved);
      setJustApproved(null);
    }
  }, [ready, justApproved]);

  return (
    <AuthenticatedLayout user={auth?.user} header={<h2 className="text-xl font-semibold">Dispatch</h2>}>
      <Head title="Dispatch" />
      <div className="p-4 grid grid-cols-1 lg:grid-cols-2 gap-4">

        {/* Pending */}
        <section>
          <h3 className="text-sm uppercase tracking-wide text-gray-500 mb-2">Pending</h3>
          {pending.length === 0 && <div className="text-sm text-gray-500">No pending requests.</div>}
          {pending.map(r => (
            <RequestCard
              key={r.id}
              r={{ ...r, status: "pending" }}
              mode="pending"
              onApprove={onApprove}
              onReject={onReject}
              onExpand={() => {}}
              expanded={false}
              vehicles={vehicles}
              drivers={drivers}
              summary={summaries[`req-${r.id}`]?.ok ? summaries[`req-${r.id}`].summary : null}
            />
          ))}
        </section>

        {/* Ready */}
        <section>
          <h3 className="text-sm uppercase tracking-wide text-gray-500 mb-2">Ready to Assign</h3>
          {ready.length === 0 && <div className="text-sm text-gray-500">No approved requests.</div>}
          {ready.map(r => (
            <RequestCard
              key={r.id}
              r={{ ...r, status: "approved" }}
              mode="ready"
              onApprove={() => {}}
              onReject={() => {}}
              onExpand={(id) => setExpandedId(id)}
              expanded={expandedId === r.id}
              vehicles={vehicles}
              drivers={drivers}
              summary={summaries[`req-${r.id}`]?.ok ? summaries[`req-${r.id}`].summary : null}
              activeTrips={active_trips}
            />
          ))}
        </section>

      </div>
    </AuthenticatedLayout>
  );
}
