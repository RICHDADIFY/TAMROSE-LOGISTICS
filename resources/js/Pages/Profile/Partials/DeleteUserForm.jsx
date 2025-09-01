import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import SecondaryButton from '@/Components/SecondaryButton';
import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function DeleteUserForm({ className = '' }) {
  const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
  const passwordInput = useRef(null);

  const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm({
    password: '',
  });

  const confirmUserDeletion = () => setConfirmingUserDeletion(true);

  const deleteUser = (e) => {
    e.preventDefault();
    destroy(route('profile.destroy'), {
      preserveScroll: true,
      onSuccess: () => closeModal(),
      onError: () => passwordInput.current?.focus(),
      onFinish: () => reset(),
    });
  };

  const closeModal = () => {
    setConfirmingUserDeletion(false);
    clearErrors();
    reset();
  };

  // Local-only input style (crisp in dark mode)
  const baseInput =
    'mt-1 block w-3/4 rounded-xl border text-gray-900 placeholder-gray-400 ' +
    'bg-white border-slate-300 shadow-sm focus:border-rose-500 focus:ring-rose-500 ' +
    'dark:bg-slate-900 dark:text-slate-100 dark:placeholder-slate-400 dark:border-slate-700 ' +
    'dark:focus:border-rose-500 dark:focus:ring-rose-500';

  return (
    <section className={`space-y-6 ${className}`}>
      <header>
        <h2 className="text-lg font-medium text-gray-900 dark:text-white">Delete Account</h2>
        <p className="mt-1 text-sm text-gray-600 dark:text-slate-300">
          Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your
          account, please download any data or information that you wish to retain.
        </p>
      </header>

      <DangerButton onClick={confirmUserDeletion}>Delete Account</DangerButton>

      <Modal show={confirmingUserDeletion} onClose={closeModal}>
        <form onSubmit={deleteUser} className="p-6">
          <h2 className="text-lg font-medium text-gray-900 dark:text-white">
            Are you sure you want to delete your account?
          </h2>

          <p className="mt-1 text-sm text-gray-600 dark:text-slate-300">
            Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your
            password to confirm you would like to permanently delete your account.
          </p>

          <div className="mt-6">
            <InputLabel htmlFor="password" value="Password" className="sr-only" />

            {/* Use a locally styled input for perfect dark-mode contrast */}
            <input
              id="password"
              type="password"
              name="password"
              ref={passwordInput}
              value={data.password}
              onChange={(e) => setData('password', e.target.value)}
              className={baseInput}
              placeholder="Password"
              autoComplete="current-password"
            />

            <InputError message={errors.password} className="mt-2" />
          </div>

          <div className="mt-6 flex justify-end gap-3">
            <SecondaryButton
  type="button"
  onClick={closeModal}
  className="
    !text-slate-800 dark:!text-slate-100
    !bg-white dark:!bg-slate-800
    !border !border-slate-300 dark:!border-slate-600
    hover:!bg-slate-50 dark:hover:!bg-slate-700
    focus:outline-none focus-visible:!ring-2 focus-visible:!ring-slate-400
  "
>
  Cancel
</SecondaryButton>


            <DangerButton className="ms-0" disabled={processing}>
              Delete Account
            </DangerButton>
          </div>
        </form>
      </Modal>
    </section>
  );
}
