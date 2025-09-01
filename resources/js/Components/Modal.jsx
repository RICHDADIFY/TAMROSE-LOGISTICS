import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';

export default function Modal({
  show = false,
  maxWidth = '2xl',
  closeable = true,
  onClose = () => {},
  children,
}) {
  const close = () => { if (closeable) onClose(); };

  const maxWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
  }[maxWidth];

  return (
    <Transition show={show} as={Fragment} appear>
      <Dialog as="div" className="fixed inset-0 z-[100] overflow-y-auto" onClose={close}>
        <div className="flex min-h-screen items-center justify-center p-4">
          {/* OVERLAY (explicitly below the panel) */}
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-200"
            enterFrom="opacity-0"
            enterTo="opacity-100"
            leave="ease-in duration-150"
            leaveFrom="opacity-100"
            leaveTo="opacity-0"
          >
            <div className="fixed inset-0 z-40 bg-black/60 dark:bg-black/70" />
          </Transition.Child>

          {/* PANEL (explicitly above the overlay) */}
          <Transition.Child
            as={Fragment}
            enter="ease-out duration-200"
            enterFrom="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
            enterTo="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-150"
            leaveFrom="opacity-100 translate-y-0 sm:scale-100"
            leaveTo="opacity-0 translate-y-2 sm:translate-y-0 sm:scale-95"
          >
            <Dialog.Panel
              className={`relative z-50 w-full overflow-hidden sm:mx-auto ${maxWidthClass}
                          rounded-2xl bg-white dark:bg-slate-900 shadow-xl
                          ring-1 ring-black/10 dark:ring-white/10`}
            >
              <div className="p-6 text-gray-900 dark:text-slate-100">
                {children}
              </div>
            </Dialog.Panel>
          </Transition.Child>
        </div>
      </Dialog>
    </Transition>
  );
}
