import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import UpdateProfileInformation from './Partials/UpdateProfileInformationForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import DeleteUserForm from './Partials/DeleteUserForm';

export default function Edit() {
  const { auth, mustVerifyEmail, status } = usePage().props;

  return (
    <AuthenticatedLayout
      user={auth?.user}
      header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">Profile</h2>}
    >
      <Head title="Profile" />
      {/* Same wrapper classes as your Ride Request page */}
      <div className="p-4 md:p-6 max-w-xl mx-auto space-y-6">
        <div className="bg-white dark:bg-slate-900 rounded-xl shadow p-4 sm:p-6">
          <UpdateProfileInformation mustVerifyEmail={mustVerifyEmail} status={status} />
        </div>

        <div className="bg-white dark:bg-slate-900 rounded-xl shadow p-4 sm:p-6">
          <UpdatePasswordForm />
        </div>

        <div className="bg-white dark:bg-slate-900 rounded-xl shadow p-4 sm:p-6">
          <DeleteUserForm />
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
