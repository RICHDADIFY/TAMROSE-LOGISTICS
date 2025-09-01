import { Link, Head } from '@inertiajs/react';

export default function Welcome() {
  return (
    <div className="relative min-h-screen flex items-center justify-center px-4 bg-gray-50 dark:bg-slate-950">
      <Head title="Welcome" />

      {/* optional subtle dark accent glow */}
      <div className="pointer-events-none fixed inset-0 -z-10 hidden dark:block
                      bg-[radial-gradient(80%_120%_at_0%_0%,rgba(34,116,66,0.10)_0%,transparent_40%)]" />

      <div className="w-full max-w-md p-8 rounded-2xl shadow
                      bg-white ring-1 ring-gray-200
                      dark:bg-slate-900 dark:ring-slate-800">
        <div className="flex items-center gap-3 mb-6">
          {/* Put your logo at public/brand/tamrose-logo.svg or change the src */}
          <img
            src="/brand/tamrose-logo.png"
            onError={(e)=>{ e.currentTarget.src='/favicon.ico'; }}
            alt="Tamrose"
            className="h-10 w-10 rounded-full"
          />
          <h1 className="text-2xl font-semibold text-gray-900 dark:text-white">
            Tamrose Logistics
          </h1>
        </div>

        <p className="text-sm text-gray-600 dark:text-slate-300 mb-8">
          Sign in or create an account to request rides and manage dispatch.
        </p>

        <div className="flex gap-3">
          <Link
            href="/login"
            className="flex-1 text-center py-2 rounded-xl bg-[#227442] text-white shadow-sm ring-1 ring-inset ring-[#227442]/40
                       hover:bg-[#1d6439] active:bg-[#1a5a34] transition"
          >
            Login
          </Link>
          <Link
            href="/register"
            className="flex-1 text-center py-2 rounded-xl bg-blue-600 text-white shadow-sm ring-1 ring-inset ring-blue-700/30
                       hover:bg-blue-700 transition"
          >
            Register
          </Link>
        </div>
      </div>
    </div>
  );
}
