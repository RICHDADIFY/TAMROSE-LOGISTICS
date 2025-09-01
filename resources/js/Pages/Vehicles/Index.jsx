import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.jsx';
import { Head, Link, router, usePage } from '@inertiajs/react';

export default function Index({ auth, vehicles }) {
  const user = (auth?.user) ?? usePage().props.auth.user;

  const toggle = (id) => router.patch(route('vehicles.toggle', id));
  const del = (id) => { if (confirm('Delete vehicle?')) router.delete(route('vehicles.destroy', id)); };

  return (
    <AuthenticatedLayout
      user={user}
      header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">Vehicles</h2>}
    >
      <Head title="Vehicles" />
      <div className="p-4 sm:p-6">
        <div className="flex items-center justify-between mb-4">
          <Link
            href={route('vehicles.create')}
            className="px-3 py-2 bg-indigo-500/90 text-white rounded-lg text-sm hover:bg-indigo-500"
          >
            Add Vehicle
          </Link>
        </div>

        {/* Mobile cards */}
        <div className="grid gap-3 sm:hidden">
          {vehicles.data.map((v) => (
            <div key={v.id} className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 p-3">
              <div className="flex items-center justify-between">
                <div className="font-semibold text-gray-800 dark:text-gray-100">{v.label}</div>
                <button
                  onClick={() => toggle(v.id)}
                  className={`text-xs px-2 py-1 rounded ${
                    v.active
                      ? 'bg-emerald-100 text-emerald-700'
                      : 'bg-gray-200 text-gray-600'
                  }`}
                  title="Toggle Active"
                >
                  {v.active ? 'Active' : 'Inactive'}
                </button>
              </div>
              <div className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                {v.type} • {v.capacity} capacity • {v.plate_number}
              </div>
              <div className="flex gap-2 mt-3">
                <Link
                  href={route('vehicles.edit', v.id)}
                  className="px-3 py-1.5 rounded bg-indigo-100 text-indigo-700 text-xs hover:bg-indigo-200"
                >
                  Edit
                </Link>
                <button
                  onClick={() => del(v.id)}
                  className="px-3 py-1.5 rounded bg-rose-100 text-rose-700 text-xs hover:bg-rose-200"
                >
                  Delete
                </button>
              </div>
            </div>
          ))}
        </div>

        {/* Table (md and up) */}
        <div className="hidden sm:block overflow-x-auto">
          <table className="min-w-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
            <thead>
              <tr className="bg-gray-50 dark:bg-slate-800 text-left text-gray-700 dark:text-gray-200">
                <th className="p-3">Label</th>
                <th className="p-3">Type</th>
                <th className="p-3">Plate</th>
                <th className="p-3">Capacity</th>
                <th className="p-3">Active</th>
                <th className="p-3"></th>
              </tr>
            </thead>
            <tbody className="text-gray-800 dark:text-gray-100">
              {vehicles.data.map((v) => (
                <tr key={v.id} className="border-t border-gray-200 dark:border-gray-700">
                  <td className="p-3">{v.label}</td>
                  <td className="p-3">{v.type}</td>
                  <td className="p-3">{v.plate_number}</td>
                  <td className="p-3">{v.capacity}</td>
                  <td className="p-3">
                    <button
                      onClick={() => toggle(v.id)}
                      className={`px-2 py-1 rounded text-xs ${
                        v.active
                          ? 'bg-emerald-100 text-emerald-700'
                          : 'bg-gray-200 text-gray-600'
                      }`}
                      title="Toggle Active"
                    >
                      {v.active ? 'Yes' : 'No'}
                    </button>
                  </td>
                  <td className="p-3 space-x-2">
                    <Link
                      href={route('vehicles.edit', v.id)}
                      className="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-sm hover:bg-indigo-200"
                    >
                      Edit
                    </Link>
                    <button
                      onClick={() => del(v.id)}
                      className="px-2 py-1 bg-rose-100 text-rose-700 rounded text-sm hover:bg-rose-200"
                    >
                      Delete
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {/* Simple pagination */}
          {vehicles.links?.length > 1 && (
            <div className="mt-4 flex flex-wrap gap-2">
              {vehicles.links.map((l, i) => (
                <Link
                  key={i}
                  href={l.url || '#'}
                  dangerouslySetInnerHTML={{ __html: l.label }}
                  className={`px-3 py-1 rounded border text-sm
                    ${l.active
                      ? 'bg-gray-900 text-white border-gray-900 dark:bg-gray-100 dark:text-gray-900 dark:border-gray-100'
                      : 'text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800'}`}
                />
              ))}
            </div>
          )}
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
