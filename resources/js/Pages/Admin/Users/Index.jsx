// resources/js/Pages/Admin/Users/Index.jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export default function UsersIndex() {
  const { users, filters, flash, auth } = usePage().props;
  const me = auth?.user;

  // Current user's roles (strings, lowercased), robust to array-of-objects or array-of-strings
  const meRoles = (me?.roles ?? []).map(r =>
    (typeof r === 'string' ? r : (r?.name ?? '')).toLowerCase()
  );
  const meIsSuper   = meRoles.includes('super admin');
  const meIsManager = meIsSuper || meRoles.includes('logistics manager') || !!me?.is_manager;

  const [q, setQ] = useState(filters?.q ?? '');

  useEffect(() => {
    const t = setTimeout(() => {
      router.get(route('admin.users.index'), { q }, { preserveState: true, replace: true });
    }, 350);
    return () => clearTimeout(t);
  }, [q]);

  const card =
    'rounded-xl p-4 bg-white ring-1 ring-gray-200 ' +
    'dark:bg-slate-900 dark:ring-slate-800';

  const pill = (t) =>
    `px-2 py-0.5 rounded text-xs ${t === 'Driver'
      ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
      : t === 'Logistics Manager'
      ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
      : t === 'Super Admin'
      ? 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-900/30 dark:text-fuchsia-300'
      : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
    }`;

  return (
    <AuthenticatedLayout
      header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">Manage Users</h2>}
    >
      <Head title="Manage Users" />

      <div className="max-w-5xl mx-auto p-4 md:p-6">
        {/* Flash */}
        {(flash?.success || flash?.error) && (
          <div className={`${card} mb-4`}>
            <p className={`${flash.success ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300'}`}>
              {flash.success || flash.error}
            </p>
          </div>
        )}

        {/* Search */}
        <div className={`${card} mb-4`}>
          <label className="block text-sm text-slate-700 dark:text-slate-200 mb-2">Search users</label>
          <input
            value={q}
            onChange={(e)=>setQ(e.target.value)}
            placeholder="Search by name, email or phone…"
            className="w-full rounded-lg border bg-white border-slate-300
                       focus:border-blue-500 focus:ring-blue-500
                       dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
          />
        </div>

        {/* List */}
        <div className={`${card}`}>
          <div className="overflow-x-auto">
            <table className="min-w-full text-sm">
              <thead>
                <tr className="text-left text-slate-500 dark:text-slate-400">
                  <th className="py-2 pe-4">Name</th>
                  <th className="py-2 pe-4">Email</th>
                  <th className="py-2 pe-4">Phone</th>
                  <th className="py-2 pe-4">Roles</th>
                  <th className="py-2 pe-2 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-200 dark:divide-slate-800">
                {users.data.map(u => {
                  const names = u.roles || [];
                  const isDriver  = names.includes('Driver');
                  const isSuper   = names.includes('Super Admin');
                  const isManager = names.includes('Logistics Manager');

                  const isSelf = me?.id === u.id;

                  // Promote/Revert rules
                  const canMakeDriver   = !isSelf && !isSuper && !isManager && !isDriver;
                  const canRevertStaff  = !isSelf && isDriver;

                  // Delete rules (server re-checks: not self, managers can’t delete managers/supers, not last super, etc.)
                  const canDelete =
                    (meIsSuper && !isSelf) ||
                    (meIsManager && !isSelf && !isSuper && !isManager);

                  return (
                    <tr key={u.id} className="text-slate-800 dark:text-slate-200">
                      <td className="py-3 pe-4">{u.name}</td>
                      <td className="py-3 pe-4">{u.email}</td>
                      <td className="py-3 pe-4">{u.phone || '-'}</td>
                      <td className="py-3 pe-4">
                        <div className="flex flex-wrap gap-1">
                          {names.map(r => <span key={r} className={pill(r)}>{r}</span>)}
                        </div>
                      </td>
                      <td className="py-3 pe-2">
                        <div className="flex justify-end gap-2">
                          <PromoteButton
                            disabled={!canMakeDriver}
                            href={route('users.make-driver', u.id)}
                            label="Make Driver"
                            color="green"
                          />
                          <PromoteButton
                            disabled={!canRevertStaff}
                            href={route('users.make-staff', u.id)}
                            label="Revert to Staff"
                            color="slate"
                          />
                          <DeleteButton
                            disabled={!canDelete}
                            href={route('admin.users.destroy', u.id)}
                            label="Delete"
                          />
                        </div>
                      </td>
                    </tr>
                  );
                })}

                {users.data.length === 0 && (
                  <tr>
                    <td colSpan="5" className="py-6 text-center text-slate-500 dark:text-slate-400">
                      No users found.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>

            {/* Pagination */}
            <div className="flex items-center justify-between mt-4 text-xs text-slate-600 dark:text-slate-400">
              <span>
                Showing {users.from}–{users.to} of {users.total}
              </span>
              <div className="flex gap-2">
                {users.links?.map((l, i) => (
                  <Link
                    key={i}
                    href={l.url || '#'}
                    dangerouslySetInnerHTML={{ __html: l.label }}
                    className={`px-2 py-1 rounded ${l.active
                        ? 'bg-slate-200 dark:bg-slate-800'
                        : 'hover:bg-slate-100 dark:hover:bg-slate-800'
                      } ${!l.url ? 'opacity-50 pointer-events-none' : ''}`}
                  />
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}

function PromoteButton({ href, label, color = 'green', disabled }) {
  const { post, processing } = useForm({});
  const tone = color === 'green'
    ? 'bg-[#227442] hover:bg-[#1d6439] ring-[#227442]/40'
    : 'bg-slate-700 hover:bg-slate-600 ring-slate-600/40';

  return (
    <button
      type="button"
      disabled={disabled || processing}
      onClick={() => post(href, { preserveScroll: true })}
      className={`px-3 py-1.5 rounded-lg text-white text-xs shadow-sm ring-1 ring-inset ${tone}
                  disabled:opacity-50 disabled:cursor-not-allowed`}
    >
      {processing ? 'Working…' : label}
    </button>
  );
}

function DeleteButton({ href, label = 'Delete', disabled }) {
  const { delete: destroy, processing } = useForm({});
  const onClick = () => {
    if (!confirm('Delete this account? This will disable access immediately.')) return;
    destroy(href, { preserveScroll: true });
  };
  return (
    <button
      type="button"
      disabled={disabled || processing}
      onClick={onClick}
      className="px-3 py-1.5 rounded-lg text-white text-xs shadow-sm ring-1 ring-inset
                 bg-rose-600 hover:bg-rose-700 ring-rose-700/30
                 disabled:opacity-50 disabled:cursor-not-allowed"
    >
      {processing ? 'Deleting…' : label}
    </button>
  );
}
