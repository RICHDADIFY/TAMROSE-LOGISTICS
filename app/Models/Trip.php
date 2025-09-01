<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Consignment;   // 👈 add this

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id','driver_id','direction','depart_at','return_at',
        'status','notes','created_by',
    ];

    protected $casts = [
        'depart_at' => 'datetime',
        'return_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function vehicle(){ return $this->belongsTo(Vehicle::class); }
    public function driver(){ return $this->belongsTo(User::class, 'driver_id'); }
    public function creator(){ return $this->belongsTo(User::class, 'created_by'); }

    // All requests attached to this trip
    public function requests()
    {
        return $this->hasMany(TripRequest::class, 'trip_id');
    }

    // The current/primary request for this trip (latest by id)
    public function request()
    {
        // Laravel 12 supports latestOfMany:
        return $this->hasOne(TripRequest::class, 'trip_id')->latestOfMany('id');

        // If you ever need a simpler fallback:
        // return $this->hasOne(TripRequest::class, 'trip_id')->latest('id');
    }

    public function consignments()
    {
        return $this->hasMany(Consignment::class);
    }
}
