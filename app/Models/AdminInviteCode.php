<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

class AdminInviteCode extends Model
{
    protected $fillable = [
        'code', 'code_hash', 'role', 'issued_by', 'expires_at',
        'max_uses', 'uses', 'is_revoked', 'notes', 'last_used_by', 'last_used_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'last_used_at' => 'datetime',
        'is_revoked'   => 'boolean',
    ];

    /** e.g. ABCD-EFGH */
    public static function generateCode(): string
    {
        return Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4));
    }

    /**
     * Create a new invite. Populates code and, if present, code_hash.
     * $role: 'Super Admin' | 'Logistics Manager'
     */
    public static function mint(string $role, ?int $issuerId = null, ?\DateTimeInterface $expiresAt = null, ?int $maxUses = 1): self
    {
        $table = (new static)->getTable();

        $plain = static::generateCode(); // keep to display/email
        $attrs = [
            'role' => $role,
        ];

        if (Schema::hasColumn($table, 'code'))      $attrs['code']      = $plain;
        if (Schema::hasColumn($table, 'code_hash')) $attrs['code_hash'] = Hash::make($plain);
        if (Schema::hasColumn($table, 'issued_by')) $attrs['issued_by'] = $issuerId;
        if (Schema::hasColumn($table, 'expires_at')) $attrs['expires_at'] = $expiresAt;
        if (Schema::hasColumn($table, 'max_uses'))   $attrs['max_uses']   = $maxUses;
        if (Schema::hasColumn($table, 'uses'))       $attrs['uses']       = 0;
        if (Schema::hasColumn($table, 'is_revoked')) $attrs['is_revoked'] = false;

        return static::create($attrs);
    }

    public function isValidFor(string $role): bool
    {
        if ($this->role !== $role) return false;
        if (Schema::hasColumn($this->getTable(), 'is_revoked') && $this->is_revoked) return false;
        if (Schema::hasColumn($this->getTable(), 'expires_at') && $this->expires_at && now()->greaterThan($this->expires_at)) return false;

        if (
            Schema::hasColumn($this->getTable(), 'max_uses') &&
            Schema::hasColumn($this->getTable(), 'uses') &&
            $this->max_uses !== null &&
            (int)$this->uses >= (int)$this->max_uses
        ) {
            return false;
        }
        return true;
    }

    public function redeem(?int $userId = null): void
    {
        if (Schema::hasColumn($this->getTable(), 'uses')) $this->increment('uses');

        $changes = [];
        if (Schema::hasColumn($this->getTable(), 'last_used_by')) $changes['last_used_by'] = $userId;
        if (Schema::hasColumn($this->getTable(), 'last_used_at')) $changes['last_used_at'] = now();
        if ($changes) $this->forceFill($changes)->save();
    }
}
