<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\TripRequestStatus;

class TripRequest extends Model
{
    use HasFactory;

    // Statuses
    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';   // 👈 add this
    public const STATUS_ASSIGNED  = 'assigned';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';  // 👈 optional but useful
    /**
     * Mass assignable fields
     * - Kept all your fields
     * - Added: status, approved_at, approved_by
     */
    //protected $fillable = [
        //'user_id',
       // 'origin', 'destination', 'desired_time',   // virtuals mapped to real columns via accessors/mutators
       // 'passengers', 'purpose',
       // 'status',                                   // NEW (already present in your list, kept here explicitly)
       // 'trip_id',
        //'from_location',
        //'to_location',
        // 'desired_departure',
       // 'manager_note',
       // 'direction',
       // 'approved_at',                              // NEW
       // 'approved_by',                              // NEW
   // ];
   
   protected $guarded = [];   // temporarily allow all


    /**
     * Casts
     * - Keep your datetime casts
     * - Add approved_at datetime cast
     */
    protected $casts = [
        'desired_time'      => 'datetime', // virtual
        'desired_departure' => 'datetime', // real DB column
        'desired_return'    => 'datetime', // real DB column (if used elsewhere)
        'approved_at'       => 'datetime', // NEW
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        // 'status'      => TripRequestStatus::class,
        'from_lat' => 'float', 'from_lng' => 'float',
    'to_lat'   => 'float', 'to_lng'   => 'float',
        
    ];

    /**
     * Appended accessors (so Inertia/JSON always includes them)
     */
    protected $appends = ['origin', 'destination', 'desired_time'];

    /* ---------------- Relations ---------------- */

   public function user()   { return $this->belongsTo(User::class, 'user_id'); }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    // NEW: approver relation (users.id)
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // NEW: audit trail relation (polymorphic)
    public function histories()
    {
        // subject_type = App\Models\TripRequest, subject_id = this->id
        return $this->morphMany(StatusHistory::class, 'subject');
    }

    /* ---------------- Accessors (read) ---------------- */

    // These let you read $model->origin etc. even though DB has from_location / to_location
    public function getOriginAttribute()
    {
        return $this->attributes['from_location'] ?? null;
    }

    public function getDestinationAttribute()
    {
        return $this->attributes['to_location'] ?? null;
    }

    public function getDesiredTimeAttribute()
    {
        // returns Carbon because of cast on desired_departure
        return $this->desired_departure;
    }

    /* ---------------- Mutators (write) ---------------- */

    // These let mass-assignment with 'origin','destination','desired_time' save to real columns.
    public function setOriginAttribute($value)
    {
        $this->attributes['from_location'] = $value;
    }

    public function setDestinationAttribute($value)
    {
        $this->attributes['to_location'] = $value;
    }

    public function setDesiredTimeAttribute($value)
    {
        $this->attributes['desired_departure'] = $value;
    }
    
   
public function requester()
{
    return $this->belongsTo(User::class, 'user_id');
}



public function driver() { return $this->belongsTo(User::class, 'driver_id'); }

}
