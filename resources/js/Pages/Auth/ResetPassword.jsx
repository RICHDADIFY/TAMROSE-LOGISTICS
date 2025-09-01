import { Head, useForm, Link } from '@inertiajs/react';

export default function ResetPassword({ email, token }) {
  const { data, setData, post, processing, errors } = useForm({
    token, email, password: '', password_confirmation: ''
  });

  const submit = (e) => {
    e.preventDefault();
    post('/reset-password');
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-gray-900">
      <Head title="Reset password" />
      <form onSubmit={submit} className="w-full max-w-md p-8 bg-white dark:bg-gray-800 rounded-2xl shadow">
        <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-6">Reset password</h2>

        <label className="block text-sm mb-1 text-gray-700 dark:text-gray-200">Email</label>
        <input type="email" value={data.email} onChange={(e)=>setData('email', e.target.value)}
               className="w-full mb-3 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required />

        <label className="block text-sm mb-1 text-gray-700 dark:text-gray-200">New password</label>
        <input type="password" value={data.password} onChange={(e)=>setData('password', e.target.value)}
               className="w-full mb-3 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required />
        {errors.password && <p className="text-red-600 text-sm -mt-2 mb-2">{errors.password}</p>}

        <label className="block text-sm mb-1 text-gray-700 dark:text-gray-200">Confirm password</label>
        <input type="password" value={data.password_confirmation} onChange={(e)=>setData('password_confirmation', e.target.value)}
               className="w-full mb-4 rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100" required />

        <button disabled={processing}
                className="w-full py-2 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition">
          {processing ? 'Resetting…' : 'Reset password'}
        </button>

        <div className="mt-4 text-sm">
          <Link href="/login" className="text-blue-600">Back to login</Link>
        </div>
      </form>
    </div>
  );
}
