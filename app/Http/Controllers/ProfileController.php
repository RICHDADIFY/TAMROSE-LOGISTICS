<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
{
    $user = $request->user();

    // Validate fields (ignore soft-deleted rows in unique rules)
    $validated = $request->validate([
        'name'   => ['required','string','max:191'],
        'email'  => [
            'required','email','max:191',
            \Illuminate\Validation\Rule::unique('users','email')
                ->ignore($user->id)
                ->whereNull('deleted_at'),
        ],
        'phone'  => [
            'required','string','max:32',
            \Illuminate\Validation\Rule::unique('users','phone')
                ->ignore($user->id)
                ->whereNull('deleted_at'),
        ],
        'avatar' => ['nullable','image','mimes:jpg,jpeg,png,webp','max:3072'],
    ]);

    $user->name  = $validated['name'];
    $user->email = $validated['email'];
    $user->phone = $validated['phone'];

    // If email changed, mark as unverified (Fortify will handle the flow)
    if ($user->isDirty('email')) {
        $user->email_verified_at = null;
    }

    // Process avatar if provided
    if ($request->hasFile('avatar')) {
        $oldPath = $user->profile_photo_path;

        // WebP 320x320 (Intervention v3)
        $image = \Intervention\Image\Laravel\Facades\Image::read(
            $request->file('avatar')->getRealPath()
        )->orientate()->cover(320, 320);

        $webp = (string) $image->toWebp(82);
        $dir = 'avatars/'.date('Y').'/'.date('m');
        $filename = \Illuminate\Support\Str::uuid().'.webp';
        \Illuminate\Support\Facades\Storage::disk('public')->put("$dir/$filename", $webp);

        $user->profile_photo_path = "$dir/$filename";

        // delete previous file (if any)
        if ($oldPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
        }
    }

    $user->save();

    if ($user->wasChanged('email')) {
        $user->sendEmailVerificationNotification();
    }

    // back to the form; your Inertia shared props will refresh user
    return \Illuminate\Support\Facades\Redirect::route('profile.edit')
        ->with('status', 'profile-updated');
}

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
