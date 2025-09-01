// resources/js/Layouts/AuthenticatedLayout.jsx
import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
  const { auth } = usePage().props;
  const user = auth?.user;


  const mobileItem =
  'rounded-md text-gray-800 hover:bg-gray-100 ' +
  'dark:text-slate-100 dark:hover:bg-slate-800 dark:hover:text-white';


  // --- Role helpers (prefer roles array; fall back to legacy flags) ---
  const roles = (user?.roles ?? []).map(r =>
    (typeof r === 'string' ? r : String(r)).toLowerCase()
  );
  const hasRole = (name) => roles.includes(name.toLowerCase());

  const isSuperAdmin = hasRole('super admin'); // optional override
  const isManager = isSuperAdmin || hasRole('logistics manager') || !!user?.is_manager;
  const isDriver  = hasRole('driver') || !!user?.is_driver;
  const isStaff   = hasRole('staff') || (!isManager && !isDriver);

  // One place to decide the My Trips link
  const myTripsHref   = isDriver ? (route().has('trips.my') ? route('trips.my') : '/my-trips')
                                 : route('trips.index');
  const myTripsActive = isDriver ? route().current('trips.my') : route().current('trips.*');

  const [showingNavigationDropdown, setShowingNavigationDropdown] = useState(false);

  return (
    <div className="min-h-screen bg-gray-100 dark:bg-slate-900">
      <nav className="border-b border-gray-100 bg-white dark:bg-slate-900 dark:border-slate-800">
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="flex h-16 justify-between">
            <div className="flex">
              <div className="flex shrink-0 items-center">
                <Link href="/">
                  <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800 dark:text-gray-100" />
                </Link>
              </div>

              {/* Desktop menu */}
              <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                <NavLink href={route('dashboard')} active={route().current('dashboard')}>
                  Dashboard
                </NavLink>

                {/* Vehicles — managers only */}
                {isManager && (
                  <NavLink
                    href={route('vehicles.index')}
                    active={route().current('vehicles.*') || route().current('vehicles')}
                  >
                    Vehicles
                  </NavLink>
                )}

                {/* Ride Requests — everyone logged in */}
                <NavLink
                  href="/ride-requests"
                  active={route().current('ride-requests.*') || route().current('ride-requests')}
                >
                  Ride Requests
                </NavLink>

                {/* My Trips — drivers go to /my-trips, staff to Trips index */}
                {(isDriver || isStaff) && (
                  <NavLink href={myTripsHref} active={myTripsActive}>
                    My Trips
                  </NavLink>
                )}

                {/* Dispatch — managers only */}
                {isManager && (
                  <NavLink href={route('dispatch.index')} active={route().current('dispatch.index')}>
                    Dispatch
                  </NavLink>
                )}

                {/* Trips board — managers only */}
                {isManager && (
                  <NavLink href={route('trips.index')} active={route().current('trips.*')}>
                    Trips
                  </NavLink>
                )}

                {isSuperAdmin && (
                  <NavLink
                    href={route('admin.invites.index')}
                    active={route().current('admin.invites.index')}
                  >
                    Admin Invites
                  </NavLink>
                )}

               {/* Manage Users — visible to Super Admin + Logistics Manager */}
                {(isSuperAdmin || isManager) && (
                  <NavLink
                    href={route('admin.users.index')}
                    active={route().current('admin.users.index')}
                  >
                    Manage Users
                  </NavLink>
                )}


              </div>
            </div>

            {/* Profile dropdown */}
            <div className="hidden sm:ms-6 sm:flex sm:items-center">
              <div className="relative ms-3">
                <Dropdown>
                  <Dropdown.Trigger>
                    <span className="inline-flex rounded-md">
                      <button
                        type="button"
                        className="inline-flex items-center rounded-md border border-transparent bg-white dark:bg-slate-800 px-3 py-2 text-sm font-medium leading-4 text-gray-500 dark:text-gray-200 transition duration-150 ease-in-out hover:text-gray-700 dark:hover:text-white focus:outline-none"
                      >
                        {user?.name}
                        <svg className="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                          <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                        </svg>
                      </button>
                    </span>
                  </Dropdown.Trigger>
                  <Dropdown.Content>
                    <Dropdown.Link href={route('profile.edit')}>Profile</Dropdown.Link>
                    <Dropdown.Link href={route('logout')} method="post" as="button">
                      Log Out
                    </Dropdown.Link>
                  </Dropdown.Content>
                </Dropdown>
              </div>
            </div>

            {/* Mobile hamburger */}
            <div className="-me-2 flex items-center sm:hidden">
              <button
                onClick={() => setShowingNavigationDropdown(prev => !prev)}
                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-slate-700 dark:text-gray-200 focus:outline-none"
              >
                <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                  <path className={!showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                  <path className={showingNavigationDropdown ? 'inline-flex' : 'hidden'} strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        {/* Mobile menu */}
        <div
  className={`${showingNavigationDropdown ? 'block' : 'hidden'} sm:hidden
              bg-white dark:bg-slate-950
              border-t border-gray-200 dark:border-slate-900`}
  style={{ WebkitTapHighlightColor: 'transparent' }}   // ← remove white tap flash
>


          <div className="space-y-1 pb-3 pt-2">
            <ResponsiveNavLink href={route('dashboard')} active={route().current('dashboard')}
              className={mobileItem}>
              Dashboard
            </ResponsiveNavLink>

            {/* Vehicles (mobile) */}
            {isManager && (
              <ResponsiveNavLink
                href={route('vehicles.index')}
                active={route().current('vehicles.*') || route().current('vehicles')}
              
              className={mobileItem}>
                Vehicles
              </ResponsiveNavLink>
            )}

            {/* Ride Requests (mobile) */}
            <ResponsiveNavLink
              href="/ride-requests"
              active={route().current('ride-requests.*') || route().current('ride-requests')}
              className={mobileItem}
            >
              Ride Requests
            </ResponsiveNavLink>

            {/* My Trips (mobile) */}
            {(isDriver || isStaff) && (
              <ResponsiveNavLink href={myTripsHref} active={myTripsActive}
              className={mobileItem}>
                
                My Trips
              </ResponsiveNavLink>
            )}

            {/* Dispatch (mobile) */}
            {isManager && (
              <ResponsiveNavLink href={route('dispatch.index')} active={route().current('dispatch.index')}
              className={mobileItem}>
                Dispatch
              </ResponsiveNavLink>
            )}

            {/* Trips (mobile) */}
            {isManager && (
              <ResponsiveNavLink href={route('trips.index')} active={route().current('trips.*')}
              className={mobileItem}>
                Trips
              </ResponsiveNavLink>
            )}

                      {isSuperAdmin && (
            <ResponsiveNavLink
              href={route('admin.invites.index')}
              active={route().current('admin.invites.index')}
            >
              Admin Invites
            </ResponsiveNavLink>
          )}

          {(isSuperAdmin || isManager) && (
            <ResponsiveNavLink
              href={route('admin.users.index')}
              active={route().current('admin.users.index')}
            >
              Manage Users
            </ResponsiveNavLink>
          )}


          </div>

          {/* Profile block */}
          <div className="border-t border-gray-200 dark:border-slate-700 pb-1 pt-4">
            <div className="px-4">
              <div className="text-base font-medium text-gray-800 dark:text-gray-100">{user?.name}</div>
              <div className="text-sm font-medium text-gray-500 dark:text-gray-300">{user?.email}</div>
            </div>
            <div className="mt-3 space-y-1">
              <ResponsiveNavLink href={route('profile.edit')}>Profile</ResponsiveNavLink>
              <ResponsiveNavLink method="post" href={route('logout')} as="button">
                Log Out
              </ResponsiveNavLink>
            </div>
          </div>
        </div>
      </nav>

      {header && (
        <header className="bg-white dark:bg-slate-800 shadow dark:shadow-slate-900/20">
          <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">{header}</div>
        </header>
      )}

      <main>{children}</main>
    </div>
  );
}
