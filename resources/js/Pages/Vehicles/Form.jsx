// resources/js/Pages/Vehicles/Form.jsx
import { useForm, Link } from '@inertiajs/react';
import { useEffect } from 'react';

export default function Form({ model = null, submit }) {
  // Align fields to your schema used in Index.jsx
  const { data, setData, post, processing, errors, put } = useForm({
    label: model?.label ?? '',
    type: model?.type ?? 'Bus',
    plate_number: model?.plate_number ?? '',
    capacity: model?.capacity ?? 1,
    active: model?.active ?? true,
    // optional extras if your table has them (make/model/year/notes)
    make: model?.make ?? '',
    model: model?.model ?? '',
    year: model?.year ?? '',
    notes: model?.notes ?? '',
  });

  useEffect(() => {
    if (model) {
      // Ensure form syncs if model prop changes
      setData({
        label: model.label ?? '',
        type: model.type ?? 'Bus',
        plate_number: model.plate_number ?? '',
        capacity: model.capacity ?? 1,
        active: Boolean(model.active),
        make: model.make ?? '',
        model: model.model ?? '',
        year: model.year ?? '',
        notes: model.notes ?? '',
      });
    }
  }, [model]);

  const onSubmit = (e) => {
    e.preventDefault();
    if (submit) {
      submit(data); // parent handles router.post/put
    } else {
      post(route('vehicles.store'));
    }
  };

  return (
    <form onSubmit={onSubmit} className="space-y-4">
      {/* Basics */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Label</label>
          <input
            type="text"
            value={data.label}
            onChange={(e) => setData('label', e.target.value)}
            placeholder="e.g., Toyota Hiace"
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          />
          {errors.label && <p className="text-rose-600 text-xs mt-1">{errors.label}</p>}
        </div>

        <div>
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Type</label>
          <select
            value={data.type}
            onChange={(e) => setData('type', e.target.value)}
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          >
            <option>Bus</option>
            <option>Car</option>
            <option>Truck</option>
            <option>SUV</option>
            <option>Van</option>
          </select>
          {errors.type && <p className="text-rose-600 text-xs mt-1">{errors.type}</p>}
        </div>

        <div>
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Plate Number</label>
          <input
            type="text"
            value={data.plate_number}
            onChange={(e) => setData('plate_number', e.target.value.toUpperCase())}
            placeholder="e.g., ABC-123-XY"
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          />
          {errors.plate_number && <p className="text-rose-600 text-xs mt-1">{errors.plate_number}</p>}
        </div>

        <div>
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Capacity</label>
          <input
            type="number"
            min="1"
            value={data.capacity}
            onChange={(e) => setData('capacity', e.target.value)}
            placeholder="e.g., 14"
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          />
          {errors.capacity && <p className="text-rose-600 text-xs mt-1">{errors.capacity}</p>}
        </div>

        {/* Active */}
        <div className="sm:col-span-2">
          <label className="inline-flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
            <input
              type="checkbox"
              checked={!!data.active}
              onChange={(e) => setData('active', e.target.checked)}
              className="rounded border-gray-300 dark:border-gray-700"
            />
            Active
          </label>
          {errors.active && <p className="text-rose-600 text-xs mt-1">{errors.active}</p>}
        </div>

        {/* Optional extras (hide if you don't need them) */}
        <div>
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Make</label>
          <input
            type="text"
            value={data.make}
            onChange={(e) => setData('make', e.target.value)}
            placeholder="e.g., Toyota"
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          />
        </div>

        <div>
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Model</label>
          <input
            type="text"
            value={data.model}
            onChange={(e) => setData('model', e.target.value)}
            placeholder="e.g., Hiace"
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          />
        </div>

        <div>
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Year</label>
          <input
            type="number"
            min="1990"
            max={new Date().getFullYear() + 1}
            value={data.year}
            onChange={(e) => setData('year', e.target.value)}
            placeholder="e.g., 2018"
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          />
        </div>

        <div className="sm:col-span-2">
          <label className="block text-sm font-semibold text-gray-700 dark:text-gray-200">Notes</label>
          <textarea
            rows={3}
            value={data.notes}
            onChange={(e) => setData('notes', e.target.value)}
            placeholder="Optional notes (condition, assigned unit, etc.)"
            className="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100"
          />
        </div>
      </div>

      {/* Actions */}
      <div className="pt-2 flex items-center justify-between gap-3">
        <Link href={route('vehicles.index')} className="text-sm text-gray-600 dark:text-gray-300 hover:underline">
          Cancel
        </Link>
        <button
          type="submit"
          className="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm hover:opacity-90 disabled:opacity-50"
          disabled={processing}
        >
          {processing ? 'Saving…' : (model ? 'Update Vehicle' : 'Save Vehicle')}
        </button>
      </div>
    </form>
  );
}
