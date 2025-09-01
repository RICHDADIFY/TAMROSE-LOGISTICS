import { Link } from '@inertiajs/react';

export default function ResponsiveNavLink({ href, active = false, method, as, children, className = '' }) {
  const base =
    'block w-full rounded-md px-4 py-3 text-base font-medium transition select-none ' +
    'focus:outline-none focus-visible:outline-none active:outline-none'; // ← no white outline

  const light = active
    ? 'bg-emerald-50 text-emerald-700'
    : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900';

  const dark = active
    ? 'dark:bg-slate-800 dark:text-white'
    : 'dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white';

  return (
    <Link
      href={href}
      method={method}
      as={as}
      className={`${base} ${light} ${dark} ${className}`}
      style={{ WebkitTapHighlightColor: 'transparent' }} // ← kill mobile tap flash
    >
      {children}
    </Link>
  );
}
