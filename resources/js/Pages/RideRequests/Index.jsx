import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Link, usePage } from '@inertiajs/react';

function StatusChip({ status }) {
  const classes = {
    pending:   'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200',
    assigned:  'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-200',
    rejected:  'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200',
    completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200',
  }[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200';

  return (
    <span className={`text-xs px-2 py-1 rounded-full ${classes}`}>
      {status}
    </span>
  );
}

export default function Index() {
  const { auth, requests, flash } = usePage().props;

  return (
    <AuthenticatedLayout
      user={auth?.user}
      header={
        <div className="flex items-center justify-between">
          <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">
            My Ride Requests
          </h2>
          <Link
            href="/ride-requests/create"
            className="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:opacity-90"
          >
            New Request
          </Link>
        </div>
      }
    >
      <div className="p-4 sm:p-6 max-w-4xl mx-auto">
        {/* flash msg */}
        {flash?.success && (
          <div className="mb-4 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-200 px-3 py-2">
            {flash.success}
          </div>
        )}

        {/* empty state */}
        {requests.data.length === 0 ? (
          <div className="rounded-xl border border-gray-200 dark:border-gray-700 p-6 text-center">
            <p className="text-gray-600 dark:text-gray-300 mb-3">
              You haven’t made any ride requests yet.
            </p>
            <Link
              href="/ride-requests/create"
              className="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:opacity-90"
            >
              Create your first request
            </Link>
          </div>
        ) : (
          <div className="space-y-3">
            {requests.data.map((r) => (
              <Link
                key={r.id}
                href={`/ride-requests/${r.id}`}
                className="block rounded-xl p-3 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
              >
                <div className="flex items-center justify-between">
                  <div className="font-semibold text-gray-800 dark:text-gray-100">
                    {r.origin} → {r.destination}
                  </div>
                  <StatusChip status={r.status} />
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                  {new Date(r.desired_time).toLocaleString()} • {r.passengers} pax
                </div>
                {r.purpose && (
                  <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    {r.purpose}
                  </div>
                )}
              </Link>
            ))}
          </div>
        )}

        {/* pagination */}
        {requests.links?.length > 1 && (
          <div className="flex flex-wrap gap-2 mt-5">
            {requests.links.map((l, i) => (
              <Link
                key={i}
                href={l.url || '#'}
                className={`text-sm px-2 py-1 rounded border
                  ${l.active
                    ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-100 dark:text-gray-900 dark:border-gray-100'
                    : 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'}`}
                dangerouslySetInnerHTML={{ __html: l.label }}
              />
            ))}
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  );
}
