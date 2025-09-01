<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverLocation extends Model
{
    
        protected $fillable = ['trip_id','driver_id','lat','lng','heading','speed','recorded_at'];
    

    protected $casts = 
        ['recorded_at' => 'datetime'];
    

    public function trip()
    {
        return $this->belongsTo(\App\Models\Trip::class);
    }

    public function driver()
    {
        return $this->belongsTo(\App\Models\User::class, 'driver_id');
    }
}
