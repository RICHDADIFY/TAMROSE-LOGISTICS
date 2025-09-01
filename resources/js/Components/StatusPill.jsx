import React from 'react';

const MAP = {
  loaded:            'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
  enroute:           'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
  onsite:            'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
  delivered:         'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
  return_collected:  'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
  return_delivered:  'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-200',
  failed:            'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
};

const LABEL = {
  loaded: 'Loaded',
  enroute: 'Enroute',
  onsite: 'On Site',
  delivered: 'Delivered',
  return_collected: 'Return Collected',
  return_delivered: 'Return Delivered',
  failed: 'Failed',
};

export default function StatusPill({ type }) {
  const klass = MAP[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
  const text = LABEL[type] || (type ?? 'Unknown');
  return <span className={`inline-flex items-center text-xs font-medium px-2 py-0.5 rounded ${klass}`}>{text}</span>;
}
