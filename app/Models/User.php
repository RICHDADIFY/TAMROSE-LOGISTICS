<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;   // ✅ ADD THIS LINE
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, HasFactory, Notifiable; // ✅ Trait is now resolved

     use SoftDeletes; // ← add

    /** @use HasFactory<\Database\Factories\UserFactory> */
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',                // ← NEW
        'profile_photo_path',   // ← NEW
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    // Expose a URL for the frontend (used in the driver card)
    protected $appends = [
        'profile_photo_url',     // ← NEW
    ];
    
    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            // assumes the file is stored on the 'public' disk
            return Storage::disk('public')->url($this->profile_photo_path);
        }

        // Fallback avatar
        return 'https://ui-avatars.com/api/?name='
            . urlencode($this->name ?? 'User')
            . '&background=10B981&color=fff';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_super_admin' => 'boolean', 
            'password' => 'hashed',
        ];
    }
}
