<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Models\AdminInviteCode;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Laravel\Facades\Image as ImageManager;
use Illuminate\Http\UploadedFile; // <-- add

class CreateNewUser
{
    public function create(array $input): User
    {
        validator($input, [
            'name'        => ['required','string','max:191'],
            'email'       => ['required','string','email','max:191','unique:users,email'],
            'phone'       => ['required','string','max:32','unique:users,phone'],
            'password'    => ['required','string','min:8','confirmed'],
            'role'        => ['required', Rule::in(['Staff','Logistics Manager','Super Admin'])],
            'admin_code'  => ['nullable','string','max:64'],
            'avatar'      => ['required','image','mimes:jpg,jpeg,png,webp','max:3072'],
        ])->validate();

        $requestedRole = $input['role'];
        $adminRoles    = ['Super Admin','Logistics Manager'];

        // Require & validate admin invite when needed
        $invite = null;
        if (in_array($requestedRole, $adminRoles, true)) {
            $code = trim((string)($input['admin_code'] ?? ''));
            if ($code === '') {
                throw ValidationException::withMessages(['admin_code' => 'Valid admin code is required.']);
            }
            $invite = AdminInviteCode::validatePlaintext($requestedRole, $code);
            if (!$invite) {
                throw ValidationException::withMessages(['admin_code' => 'Invalid or expired admin code.']);
            }
        }

        // --- Read file robustly (critical) ---
        $avatarFile = request()->file('avatar') ?? ($input['avatar'] ?? null);
        if (!$avatarFile instanceof UploadedFile) {
            throw ValidationException::withMessages(['avatar' => 'Avatar upload failed. Please select the image again.']);
        }

        // Process -> WebP and save on public disk
        $img   = ImageManager::read($avatarFile->getRealPath())->orientate()->cover(320, 320);
        $webp  = (string) $img->toWebp(82);
        $dir   = 'avatars/'.date('Y').'/'.date('m');
        $name  = Str::uuid().'.webp';
        Storage::disk('public')->put("{$dir}/{$name}", $webp);
        $path  = "{$dir}/{$name}";

        return DB::transaction(function () use ($input, $requestedRole, $invite, $path) {
            $user = User::create([
                'name'               => $input['name'],
                'email'              => $input['email'],
                'phone'              => $input['phone'],
                'password'           => Hash::make($input['password']),
                'profile_photo_path' => $path,  // <-- will now be non-null
            ]);

            // Assign role
            $user->syncRoles([$requestedRole === 'Staff' ? 'Staff' : $requestedRole]);

            // Mirror flags (back-compat)
            $isLM = $requestedRole === 'Logistics Manager';
            $isSU = $requestedRole === 'Super Admin';
            if ($user->isFillable('is_manager') || \Schema::hasColumn('users','is_manager')) {
                $user->forceFill(['is_manager' => $isLM || $isSU])->save();
            }
            if ($user->isFillable('is_super_admin') || \Schema::hasColumn('users','is_super_admin')) {
                $user->forceFill(['is_super_admin' => $isSU])->save();
            }

            if ($invite) {
                $invite->consume();
            }

            return $user;
        });
    }
}
