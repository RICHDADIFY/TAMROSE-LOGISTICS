<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Consignment extends Model
{
    protected $fillable = [
        'trip_id','vessel_id','port_id',
        'type','status',
        'destination_label',
        'contact_name','contact_phone','contact_email',
        'otp_code','otp_expires_at',
        'evidence_json',
        'require_otp',
        'related_consignment_id',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'evidence_json'  => 'array',
         'require_otp' => 'boolean',
    ];

    public function trip()      { return $this->belongsTo(Trip::class); }
    public function vessel()    { return $this->belongsTo(Vessel::class); }
    public function port()      { return $this->belongsTo(Port::class); }
    public function items()     { return $this->hasMany(ConsignmentItem::class); }
    public function events()    { return $this->hasMany(CustodyEvent::class); }
    public function related()   { return $this->belongsTo(Consignment::class, 'related_consignment_id'); }

    // Convenience scopes
    public function scopeOutbound($q){ return $q->where('type','outbound'); }
    public function scopeReturn($q){ return $q->where('type','return'); }
    public function custodyEvents(){ return $this->hasMany(CustodyEvent::class); }

    // e.g., in Consignment model
    public function generateDeliveryOtp(?int $length = null): string
{
    $len = $length ?? (int)($this->otp_length ?? 4);
    $max = (10 ** $len) - 1;
    $code = str_pad((string) random_int(0, $max), $len, '0', STR_PAD_LEFT);

    $ttl = (int) ($this->otp_ttl_minutes ?? 30);
    $this->forceFill([
        'delivery_otp'   => $code,
        'otp_expires_at' => now()->addMinutes($ttl),
    ])->save();

    return $code;
}



// "latest event" for the pill/timeline header
public function latestEvent()
{
    // prefer occurred_at, then id as tiebreaker
    return $this->hasOne(\App\Models\CustodyEvent::class)
        ->latest('occurred_at')
        ->latest('id');
}

   
}
