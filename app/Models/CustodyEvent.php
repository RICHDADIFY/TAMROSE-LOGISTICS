<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustodyEvent extends Model
{
    protected $fillable = [
        'consignment_id', 'user_id', 'type', 'occurred_at',
        'lat','lng','receiver_name','receiver_phone',
        'otp_used','signature_path','photos_json','note',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'photos_json' => 'array',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
