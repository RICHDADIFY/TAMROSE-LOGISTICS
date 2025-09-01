import { Head, useForm, Link } from '@inertiajs/react';

export default function ForgotPassword() {
  const { data, setData, post, processing, errors, recentlySuccessful } = useForm({ email: '' });

  const submit = (e) => {
    e.preventDefault();
    post('/forgot-password', { preserveScroll: true });
  };

  const inputBase =
    'w-full rounded-lg border text-gray-900 placeholder-gray-400 ' +
    'bg-white border-slate-300 focus:border-blue-500 focus:ring-blue-500 ' +
    'dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400 dark:border-slate-700 ' +
    'dark:focus:border-blue-500 dark:focus:ring-blue-500';

  return (
    <div className="relative min-h-screen flex items-center justify-center px-4 bg-gray-50 dark:bg-slate-950">
      <Head title="Forgot password" />

      {/* optional subtle dark accent */}
      <div className="pointer-events-none fixed inset-0 -z-10 hidden dark:block
                      bg-[radial-gradient(70%_120%_at_0%_0%,rgba(34,116,66,0.10)_0%,transparent_45%)]" />

      <form
        onSubmit={submit}
        className="w-full max-w-md p-8 rounded-2xl shadow ring-1 ring-gray-200 dark:ring-slate-800 bg-white dark:bg-slate-900"
      >
        <div className="flex items-center gap-3 mb-4">
          <img
            src="/brand/tamrose-logo.svg"
            onError={(e)=>{ e.currentTarget.src='/favicon.ico'; }}
            alt="Tamrose"
            className="h-9 w-9 rounded-full"
          />
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Forgot password</h2>
        </div>

        <p className="text-sm text-gray-600 dark:text-slate-300 mb-4">
          Enter your account email and we’ll send a reset link.
        </p>

        <label className="block text-sm mb-1 text-slate-700 dark:text-slate-200">Email</label>
        <input
          type="email"
          value={data.email}
          onChange={(e)=>setData('email', e.target.value)}
          className={inputBase + ' mb-3'}
          required
          autoComplete="email"
        />
        {errors.email && <p className="text-rose-600 text-sm mb-2">{errors.email}</p>}

        <button
          disabled={processing}
          className="w-full py-2 rounded-xl bg-[#227442] text-white shadow-sm ring-1 ring-inset ring-[#227442]/40
                     hover:bg-[#1d6439] active:bg-[#1a5a34]
                     transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#227442] focus-visible:ring-offset-2"
        >
          {processing ? 'Sending…' : 'Email reset link'}
        </button>

        {recentlySuccessful && (
          <p className="mt-3 text-sm rounded-md px-3 py-2 bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
            Check your email for a reset link.
          </p>
        )}

        <div className="mt-4 text-sm">
          <Link href="/login" className="text-blue-600 dark:text-blue-400 hover:underline">
            Back to login
          </Link>
        </div>
      </form>
    </div>
  );
}
