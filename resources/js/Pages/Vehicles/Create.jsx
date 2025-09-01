// resources/js/Pages/Vehicles/Create.jsx
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.jsx';
import { Head, usePage, Link } from '@inertiajs/react';
import Form from './Form';

export default function Create() {
  const { auth } = usePage().props;

  return (
    <AuthenticatedLayout
      user={auth.user}
      header={
        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200">
          Add Vehicle
        </h2>
      }
    >
      <Head title="Add Vehicle" />
      <div className="p-4 sm:p-6 max-w-3xl mx-auto">
        <div className="mb-4 text-sm text-gray-600 dark:text-gray-300">
          Create a new vehicle record. Make sure plate number and capacity are accurate.
        </div>

        <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 shadow-sm">
          <div className="p-4 sm:p-6">
            <Form />
          </div>
        </div>

        <div className="mt-4">
          <Link href={route('vehicles.index')} className="text-sm text-gray-600 dark:text-gray-300 hover:underline">
            ← Back to Vehicles
          </Link>
        </div>
      </div>
    </AuthenticatedLayout>
  );
}
