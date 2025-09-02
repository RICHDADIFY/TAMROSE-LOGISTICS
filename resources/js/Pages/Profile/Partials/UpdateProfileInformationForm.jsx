// resources/js/Pages/Profile/Partials/UpdateProfileInformationForm.jsx
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import { Transition } from '@headlessui/react';
import { Link, useForm, usePage } from '@inertiajs/react';
import AvatarCapture from '@/Components/AvatarCapture';
import { useMemo, useState } from 'react';
import { router } from '@inertiajs/react';

export default function UpdateProfileInformation({ mustVerifyEmail, status, className = '' }) {
  const user = usePage().props.auth.user;
  const [avatarError, setAvatarError] = useState(null);

  const currentAvatar = useMemo(() => {
    return user?.profile_photo_url ?? (user?.profile_photo_path ? `/storage/${user.profile_photo_path}` : null);
  }, [user]);

  // need BOTH post and patch (file/no-file)
  const { data, setData, post, patch, errors, processing, recentlySuccessful } = useForm({
    name: user?.name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    avatar: null,
  });

 // state stays the same; ensure onAvatar stores a File with a filename

// ensure AvatarCapture gives us a File with a filename
const onAvatar = (file) => {
  setAvatarError(null);
  if (!file) return;
  if (file.size > 3 * 1024 * 1024) { setAvatarError('Image must be less than 3MB.'); return; }

  const asFile = file.name
    ? file
    : new File([file], 'avatar.webp', { type: file.type || 'image/webp', lastModified: Date.now() });

  setData('avatar', asFile);
};

const submit = (e) => {
  e.preventDefault();

  router.post(route('profile.save'), {
    name:  data.name,
    email: data.email,
    phone: data.phone,
    avatar: data.avatar ?? null,   // File object (or null)
  }, {
    forceFormData: true,           // ensure multipart/form-data
    preserveScroll: true,
    onError: (errs) => setAvatarError(errs?.avatar),
  });
};





  // Deep dark page + crisp dark controls (local only)
  const baseInput =
    'mt-1 block w-full rounded-xl border text-gray-900 placeholder-gray-400 ' +
    'bg-white border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 ' +
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
    <div className="mb-4">
      <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h2>
      <p className="mt-1 text-sm text-gray-700 dark:text-slate-300">
        Update your profile photo, name, email and phone.
      </p>
    </div>

    <form onSubmit={submit} encType="multipart/form-data" className="space-y-6">
      {/* Avatar */}
      <div>
        <InputLabel htmlFor="avatar" value="Profile Photo (required)" />
        <div className="mt-2">
          <AvatarCapture
            onFile={onAvatar}
            error={avatarError || errors.avatar}
            initialPreviewUrl={currentAvatar}
          />
        </div>
      </div>


          {/* Name */}
<div>
  <InputLabel htmlFor="name" value="Name" />
  <input
    id="name"
    name="name"                 // ← add
    className={baseInput}
    value={data.name}
    onChange={(e) => setData('name', e.target.value)}
    required
    autoComplete="name"
  />
  <InputError className="mt-2" message={errors.name} />
</div>

{/* Email */}
<div>
  <InputLabel htmlFor="email" value="Email" />
  <input
    id="email"
    name="email"                // ← add
    type="email"
    className={baseInput}
    value={data.email}
    onChange={(e) => setData('email', e.target.value)}
    required
    autoComplete="username"
  />
  <InputError className="mt-2" message={errors.email} />
</div>

{/* Phone */}
<div>
  <InputLabel htmlFor="phone" value="Phone" />
  <input
    id="phone"
    name="phone"                // ← add
    className={baseInput}
    value={data.phone}
    onChange={(e) => setData('phone', e.target.value)}
    required
    autoComplete="tel"
  />
  <InputError className="mt-2" message={errors.phone} />
</div>


          {/* Verify note */}
          {mustVerifyEmail && user?.email_verified_at === null && (
            <div className="rounded-lg p-3 bg-amber-50 dark:bg-amber-900/30">
              <p className="text-sm text-amber-800 dark:text-amber-200">
                Your email address is unverified.{` `}
                <Link
                  href={route('verification.send')}
                  method="post"
                  as="button"
                  className="font-medium text-blue-600 dark:text-blue-400 underline hover:opacity-80"
                >
                  Click here to re-send the verification email.
                </Link>
              </p>
              {status === 'verification-link-sent' && (
                <div className="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                  A new verification link has been sent to your email address.
                </div>
              )}
            </div>
          )}

          {/* Save */}
           <div className="flex items-center gap-4 pt-4 border-t border-gray-200 dark:border-slate-800">
        <button type="submit" disabled={processing} className={saveBtn}>Save</button>
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
