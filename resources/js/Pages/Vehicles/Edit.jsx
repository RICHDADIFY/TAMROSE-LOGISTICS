// resources/js/Pages/Vehicles/Edit.jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.jsx';
import { Head, usePage, router } from '@inertiajs/react';
import Form from './Form';

export default function Edit({ auth, vehicle }) {
  const submit = (data) => router.put(route('vehicles.update', vehicle.id), data);

  return (
    <AuthenticatedLayout
      user={auth?.user ?? usePage().props.auth.user}
      header={
        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">
          Edit Vehicle
        </h2>
      }
    >
      <Head title="Edit Vehicle" />
      <div className="p-4 sm:p-6 max-w-3xl mx-auto">
        <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 shadow-sm">
          <div className="p-4 sm:p-6">
            <Form model={vehicle} submit={submit} />
          </div>
        </div>

        <div className="mt-4">
          <a href={route('vehicles.index')} className="text-sm text-gray-600 dark:text-gray-300 hover:underline">
            ← Back to Vehicles
          </a>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
