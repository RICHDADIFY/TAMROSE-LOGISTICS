// resources/js/Pages/Trips/Index.jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';

export default function Index({ trips, filters }) {
 
  const { auth } = usePage().props;
  const isManager = !!auth?.user?.is_manager;
  const isDriver  = !!auth?.user?.is_driver;
  
    const changeManagerStatus = (id, action) => {
    router.patch(route('trips.manager-status', id), { action });
  };

  const changeDriverStatus = (id, action) => {
    router.patch(route('trips.driver-status', id), { action });
  };


  return (
    <AuthenticatedLayout
      header={<h2 className="text-2xl font-bold">Trips</h2>}
    >
      <Head title="Trips" />

      <div className="p-6 space-y-4">

        {/* (Optional) simple status filter pills */}
        {filters?.status && (
          <div className="text-sm text-gray-500">
            Filter: <span className="font-medium">{filters.status}</span>
          </div>
        )}

        {/* Desktop / tablet table */}
        <div className="hidden sm:block bg-white rounded-xl shadow overflow-x-auto">
          <table className="min-w-full text-sm">
            <thead className="bg-emerald-800 text-white uppercase text-xs">
              <tr>
                <th className="p-3 text-left">Vehicle</th>
                <th className="p-3 text-left">Driver</th>
                <th className="p-3 text-left">Depart</th>
                <th className="p-3 text-left">Return</th>
                <th className="p-3 text-left">Status</th>
                { (isManager || isDriver) && <th className="p-3 text-left">Actions</th> }
                <th className="p-3 text-right">View</th>
              </tr>
            </thead>

            <tbody>
  {trips.data.map(t => (
    <tr key={t.id} className="border-b last:border-none">
      <td className="p-3">{t.vehicle || '—'}</td>
      <td className="p-3">{t.driver || '—'}</td>
      <td className="p-3">{t.depart_at || '—'}</td>
      <td className="p-3">{t.return_at || '—'}</td>
      <td className="p-3">
        <span className="px-2 py-1 rounded-full bg-blue-100 text-blue-700">
          {t.status}
        </span>
      </td>

      {/* ✅ ACTIONS */}
      {(isManager || isDriver) && (
        <td className="p-3">
          {/* Manager quick-actions */}
          {isManager && (
            <div className="flex gap-2 flex-wrap">
              {t.status === 'scheduled' && (
                <button
                  onClick={() => changeManagerStatus(t.id, 'dispatch')}
                  className="px-2 py-1 rounded bg-emerald-600 text-white"
                >
                  Dispatch
                </button>
              )}
              {t.status === 'in-progress' && (
                <button
                  onClick={() => changeManagerStatus(t.id, 'complete')}
                  className="px-2 py-1 rounded bg-indigo-600 text-white"
                >
                  Complete
                </button>
              )}
              {['scheduled','dispatched','in-progress'].includes(t.status) && (
                <button
                  onClick={() => changeManagerStatus(t.id, 'cancel')}
                  className="px-2 py-1 rounded bg-red-600 text-white"
                >
                  Cancel
                </button>
              )}
            </div>
          )}

          {/* Driver self-actions (only on *their* trips – server enforces this too) */}
          {isDriver && (
            <div className="mt-1 flex gap-2 flex-wrap">
              {['scheduled','dispatched'].includes(t.status) && (
                <button
                  onClick={() => changeDriverStatus(t.id, 'start')}
                  className="px-2 py-1 rounded bg-amber-600 text-white"
                >
                  Start trip
                </button>
              )}
              {t.status === 'in-progress' && (
                <button
                  onClick={() => changeDriverStatus(t.id, 'complete')}
                  className="px-2 py-1 rounded bg-emerald-600 text-white"
                >
                  Complete
                </button>
              )}
            </div>
          )}
        </td>
      )}

      {/* View */}
      <td className="p-3 text-right">
        <Link href={route('trips.show', t.id)} className="text-blue-600 hover:underline">
          View
        </Link>
      </td>
    </tr>
  ))}
  {trips.data.length === 0 && (
    <tr>
      <td colSpan="7" className="p-6 text-center text-gray-500">
        No trips yet.
      </td>
    </tr>
  )}
</tbody>

          </table>
        </div>

        {/* Mobile cards */}
        {/* Mobile cards */}
<div className="sm:hidden space-y-3">
  {trips.data.length === 0 && (
    <div className="bg-white rounded-xl shadow p-4 text-center text-gray-500">
      No trips yet.
    </div>
  )}

  {trips.data.map((t) => (
    <div key={t.id} className="bg-white rounded-xl shadow p-4">
      <div className="flex items-center justify-between">
        <div className="font-semibold">{t.vehicle || '—'}</div>
        <span className="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">
          {t.status}
        </span>
      </div>

      <div className="mt-1 text-sm text-gray-600">{t.driver || '—'}</div>
      <div className="mt-2 text-sm">
        <div><span className="font-medium">Depart:</span> {t.depart_at || '—'}</div>
        <div><span className="font-medium">Return:</span> {t.return_at || '—'}</div>
      </div>

      {(isManager || isDriver) && (
        <div className="mt-3 grid grid-cols-2 gap-2">
          {isManager && (
            <>
              {t.status === 'scheduled' && (
                <button
                  onClick={() => changeManagerStatus(t.id, 'dispatch')}
                  className="col-span-2 px-3 py-2 rounded bg-emerald-600 text-white"
                >
                  Dispatch
                </button>
              )}
              {t.status === 'in-progress' && (
                <button
                  onClick={() => changeManagerStatus(t.id, 'complete')}
                  className="col-span-2 px-3 py-2 rounded bg-indigo-600 text-white"
                >
                  Mark Completed
                </button>
              )}
              {['scheduled','dispatched','in-progress'].includes(t.status) && (
                <button
                  onClick={() => changeManagerStatus(t.id, 'cancel')}
                  className="col-span-2 px-3 py-2 rounded bg-red-600 text-white"
                >
                  Cancel Trip
                </button>
              )}
            </>
          )}

          {isDriver && (
            <>
              {['scheduled','dispatched'].includes(t.status) && (
                <button
                  onClick={() => changeDriverStatus(t.id, 'start')}
                  className="col-span-2 px-3 py-2 rounded bg-amber-600 text-white"
                >
                  Start Trip
                </button>
              )}
              {t.status === 'in-progress' && (
                <button
                  onClick={() => changeDriverStatus(t.id, 'complete')}
                  className="col-span-2 px-3 py-2 rounded bg-emerald-600 text-white"
                >
                  Complete Trip
                </button>
              )}
            </>
          )}

          <Link
            href={route('trips.show', t.id)}
            className="col-span-2 text-center px-3 py-2 rounded bg-gray-100 text-gray-800"
          >
            View
          </Link>
        </div>
      )}
    </div>
  ))}
</div>


        {/* Pagination */}
        <div className="mt-4 flex gap-2 flex-wrap">
          {trips.links.map((l, i) => (
            <Link
              key={i}
              href={l.url || '#'}
              className={`px-3 py-1 rounded ${l.active ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700'} ${!l.url ? 'pointer-events-none opacity-50' : ''}`}
              dangerouslySetInnerHTML={{ __html: l.label }}
            />
          ))}
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
