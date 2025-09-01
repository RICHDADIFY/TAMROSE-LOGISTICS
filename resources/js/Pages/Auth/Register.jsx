import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import AvatarCapture from '@/Components/AvatarCapture';



const ADMIN_ROLES = ['Super Admin','Logistics Manager'];

export default function Register() {
  const [avatarError, setAvatarError] = useState(null);
  const { data, setData, post, processing, errors } = useForm({
    name: '', email: '', phone: '', password: '', password_confirmation: '',
    role: 'Staff', admin_code: '', avatar: null,
  });

  useEffect(() => {
    const p = new URLSearchParams(window.location.search);
    const r = p.get('role'); 
    const c = p.get('code');
    if (r) setData('role', decodeURIComponent(r));
    if (c) setData('admin_code', c);
  }, []); // run once on mount

  const onAvatar = (file) => {
    setAvatarError(null);
    if (!file) return;
    if (file.size > 3 * 1024 * 1024) { setAvatarError('Image must be less than 3MB.'); return; }
    setData('avatar', file);
  };

  const submit = (e) => {
    e.preventDefault();
    if (!data.avatar) { setAvatarError('Profile photo is required.'); return; }
    post('/register', { forceFormData: true, preserveScroll: true });
  };

  const needsCode = ADMIN_ROLES.includes(data.role);

  // ✅ Ensures visible inputs in both themes (includes `border`)
  const inputBase =
    'w-full rounded-lg border text-gray-900 placeholder-gray-400 ' +
    'bg-white border-slate-300 focus:border-blue-500 focus:ring-blue-500 ' +
    'dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400 dark:border-slate-700 ' +
    'dark:focus:border-blue-500 dark:focus:ring-blue-500';

  return (
    <div className="min-h-screen flex items-center justify-center px-4 bg-gray-50 dark:bg-slate-950">
      <Head title="Create account" />
      <form onSubmit={submit} className="w-full max-w-lg p-6 sm:p-8 rounded-2xl shadow ring-1 ring-gray-200 dark:ring-slate-800 bg-white dark:bg-slate-900">
        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">Create account</h2>

        <div className="grid md:grid-cols-2 gap-4">
          <div className="md:col-span-2">
            <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">Full name</label>
            <input
              value={data.name}
              onChange={(e)=>setData('name', e.target.value)}
              className={inputBase}
              required
              autoComplete="name"
            />
            {errors.name && <p className="text-rose-600 text-sm mt-1">{errors.name}</p>}
          </div>

          <div>
            <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">Email</label>
            <input
              type="email"
              value={data.email}
              onChange={(e)=>setData('email', e.target.value)}
              className={inputBase}
              required
              autoComplete="username"
            />
            {errors.email && <p className="text-rose-600 text-sm mt-1">{errors.email}</p>}
          </div>

          <div>
            <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">Phone</label>
            <input
              value={data.phone}
              onChange={(e)=>setData('phone', e.target.value)}
              className={inputBase}
              required
              autoComplete="tel"
            />
            {errors.phone && <p className="text-rose-600 text-sm mt-1">{errors.phone}</p>}
          </div>

          <div>
            <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">Password</label>
            <input
              type="password"
              value={data.password}
              onChange={(e)=>setData('password', e.target.value)}
              className={inputBase}
              required
              autoComplete="new-password"
            />
            {errors.password && <p className="text-rose-600 text-sm mt-1">{errors.password}</p>}
          </div>

          <div>
            <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">Confirm password</label>
            <input
              type="password"
              value={data.password_confirmation}
              onChange={(e)=>setData('password_confirmation', e.target.value)}
              className={inputBase}
              required
              autoComplete="new-password"
            />
          </div>

          <div>
            <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">Role</label>
            <select
              value={data.role}
              onChange={(e)=>setData('role', e.target.value)}
              className={inputBase}
            >
              <option>Staff</option>
              <option>Logistics Manager</option>
              <option>Super Admin</option>
              {/* Shown but disabled by policy */}
              <option disabled>Driver (assigned by manager)</option>
            </select>
            <p className="text-xs text-slate-500 dark:text-slate-400 mt-1">
              Driver access is granted by a Logistics Manager after signup.
            </p>
            {errors.role && <p className="text-rose-600 text-sm mt-1">{errors.role}</p>}
          </div>

          {/* Admin code (enabled only for Super Admin / Logistics Manager) */}
          <div className={`${needsCode ? '' : 'opacity-50 pointer-events-none'}`}>
            <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">
              Admin code {needsCode ? '' : '(select LM/Super Admin to enable)'}
            </label>
            <input
              value={data.admin_code}
              onChange={(e)=>setData('admin_code', e.target.value)}
              disabled={!needsCode}
              className={inputBase}
            />
            {errors.admin_code && <p className="text-rose-600 text-sm mt-1">{errors.admin_code}</p>}
          </div>

          <div className="md:col-span-2">
            <AvatarCapture onFile={onAvatar} error={avatarError || errors.avatar} />
          </div>
        </div>

        <button
          disabled={processing}
          className="w-full mt-6 py-2 rounded-xl bg-blue-600 text-white shadow-sm ring-1 ring-inset ring-blue-700/30
                     hover:bg-blue-700 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
        >
          {processing ? 'Creating…' : 'Create account'}
        </button>

        <div className="mt-4 text-sm">
          <span className="text-slate-600 dark:text-slate-300">Already have an account? </span>
          <Link href="/login" className="text-blue-600 dark:text-blue-400 hover:underline">Login</Link>
        </div>
      </form>
    </div>
  );
}
