import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { useRef } from 'react';

export default function UpdatePasswordForm({ className = '' }) {
  const passwordInput = useRef(null);
  const currentPasswordInput = useRef(null);

  const { data, setData, errors, put, reset, processing, recentlySuccessful } = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  const updatePassword = (e) => {
    e.preventDefault();
    put(route('password.update'), {
      preserveScroll: true,
      onSuccess: () => reset(),
      onError: (errs) => {
        if (errs.password) {
          reset('password', 'password_confirmation');
          passwordInput.current?.focus();
        }
        if (errs.current_password) {
          reset('current_password');
          currentPasswordInput.current?.focus();
        }
      },
    });
  };

  // Local-only styles (do not affect global components)
  const baseInput =
    'mt-1 block w-full rounded-xl border text-gray-900 placeholder-gray-400 ' +
    'bg-white border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 ' +
    'dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400 dark:border-slate-700 ' +
    'dark:focus:border-emerald-500 dark:focus:ring-emerald-500';

  const saveBtn =
    'inline-flex items-center justify-center px-4 py-2 rounded-xl font-medium ' +
    'bg-emerald-600 text-white shadow-sm ring-1 ring-inset ring-emerald-700/30 ' +
    'hover:bg-emerald-700 hover:shadow transition ' +
    'focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 ' +
    'disabled:opacity-60 disabled:cursor-not-allowed ' +
    'dark:bg-emerald-700 dark:hover:bg-emerald-600 dark:ring-emerald-500/30 dark:focus-visible:ring-emerald-400';

  return (
    <section className={className}>
      <header>
        <h2 className="text-lg font-medium text-gray-900 dark:text-white">Update Password</h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-slate-300">
          Ensure your account is using a long, random password to stay secure.
        </p>
      </header>

      <form onSubmit={updatePassword} className="mt-6 space-y-6">
        {/* Current password */}
        <div>
          <InputLabel htmlFor="current_password" value="Current Password" />
          <input
            id="current_password"
            ref={currentPasswordInput}
            value={data.current_password}
            onChange={(e) => setData('current_password', e.target.value)}
            type="password"
            className={baseInput}
            autoComplete="current-password"
          />
          <InputError message={errors.current_password} className="mt-2" />
        </div>

        {/* New password */}
        <div>
          <InputLabel htmlFor="password" value="New Password" />
          <input
            id="password"
            ref={passwordInput}
            value={data.password}
            onChange={(e) => setData('password', e.target.value)}
            type="password"
            className={baseInput}
            autoComplete="new-password"
          />
          <InputError message={errors.password} className="mt-2" />
        </div>

        {/* Confirm password */}
        <div>
          <InputLabel htmlFor="password_confirmation" value="Confirm Password" />
          <input
            id="password_confirmation"
            value={data.password_confirmation}
            onChange={(e) => setData('password_confirmation', e.target.value)}
            type="password"
            className={baseInput}
            autoComplete="new-password"
          />
          <InputError message={errors.password_confirmation} className="mt-2" />
        </div>

        <div className="flex items-center gap-4">
          <button type="submit" disabled={processing} className={saveBtn}>
            Save
          </button>

          <Transition
            show={recentlySuccessful}
            enter="transition ease-in-out"
            enterFrom="opacity-0"
            leave="transition ease-in-out"
            leaveTo="opacity-0"
          >
            <p className="text-sm text-gray-600 dark:text-slate-300">Saved.</p>
          </Transition>
        </div>
      </form>
    </section>
  );
}
