import { useForm, usePage } from '@inertiajs/react';

export default function InviteCodes() {
  const { flash, invite } = usePage().props ?? {};

  const { data, setData, post, processing, errors, reset } = useForm({
    role: 'Logistics Manager',
    email: '',
    uses: 1,
    days: 7,
    notes: '',
  });

  const submit = (e) => {
    e.preventDefault();
    post(route('admin.invites.store'), {
      preserveScroll: true,
      onSuccess: () => reset('email', 'notes'),
    });
  };

  const input =
    'w-full rounded-lg border bg-white border-slate-300 ' +
    'dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100';

  return (
    // full-height wrapper so dark theme fills the screen
    <div className="min-h-screen px-4 py-6 bg-gray-50 dark:bg-slate-950">
      <div className="max-w-md mx-auto p-6 bg-white dark:bg-slate-900 rounded-xl shadow ring-1 ring-gray-200 dark:ring-slate-800">
        <h2 className="text-lg font-semibold text-slate-900 dark:text-white mb-4">Generate Admin Invite</h2>

        {flash?.success && (
          <div
            role="status"
            aria-live="polite"
            className="mb-4 rounded-lg px-3 py-2 text-sm
                       bg-emerald-50 text-emerald-800
                       dark:bg-emerald-900/20 dark:text-emerald-300 ring-1 ring-emerald-700/20"
          >
            <div className="font-medium">{flash.success}</div>

            {invite && (
              <div className="mt-2 grid grid-cols-1 gap-1 text-[13px]">
                {invite.code && (
                  <div className="flex items-center gap-2">
                    <span className="opacity-70">Code:</span>
                    <code className="px-2 py-0.5 rounded bg-black/5 dark:bg-white/5">{invite.code}</code>
                    <button
                      type="button"
                      onClick={() => navigator.clipboard?.writeText(invite.code)}
                      className="text-xs px-2 py-1 rounded bg-slate-200 hover:bg-slate-300 dark:bg-slate-800 dark:hover:bg-slate-700"
                    >
                      Copy
                    </button>
                  </div>
                )}
                {invite.role && <div><span className="opacity-70">Role:</span> {invite.role}</div>}
                {invite.email && <div><span className="opacity-70">Sent to:</span> {invite.email}</div>}
                {invite.expires_at && <div><span className="opacity-70">Expires:</span> {invite.expires_at}</div>}
                {invite.uses && <div><span className="opacity-70">Max uses:</span> {invite.uses}</div>}
              </div>
            )}
          </div>
        )}

        <form onSubmit={submit} className="space-y-3">
          <div>
            <label className="text-sm dark:text-slate-200">Role</label>
            <select className={input} value={data.role} onChange={(e)=>setData('role', e.target.value)}>
              <option>Logistics Manager</option>
              <option>Super Admin</option>
            </select>
            {errors.role && <p className="text-rose-600 text-sm">{errors.role}</p>}
          </div>

          <div>
            <label className="text-sm dark:text-slate-200">Send to email</label>
            <input type="email" autoComplete="email" className={input} value={data.email} onChange={(e)=>setData('email', e.target.value)} />
            {errors.email && <p className="text-rose-600 text-sm">{errors.email}</p>}
          </div>

          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="text-sm dark:text-slate-200">Max uses</label>
              <input type="number" min="1" className={input} value={data.uses} onChange={(e)=>setData('uses', e.target.value)} />
            </div>
            <div>
              <label className="text-sm dark:text-slate-200">Expires in (days)</label>
              <input type="number" min="0" className={input} value={data.days} onChange={(e)=>setData('days', e.target.value)} />
              <p className="text-[11px] text-slate-500 dark:text-slate-400">0 = never expires</p>
            </div>
          </div>

          <div>
            <label className="text-sm dark:text-slate-200">Notes (optional)</label>
            <input className={input} value={data.notes} onChange={(e)=>setData('notes', e.target.value)} />
          </div>

          <button
            disabled={processing}
            className="w-full py-2 rounded-xl bg-[#227442] text-white shadow-sm ring-1 ring-inset ring-[#227442]/40
                       hover:bg-[#1d6439] disabled:opacity-60"
          >
            {processing ? 'Sending…' : 'Generate & email code'}
          </button>
        </form>
      </div>
    </div>
  );
}
