<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Port extends Model
{
    protected $fillable = [
        'code','name','contact_name','contact_phone','contact_email','active'
    ];

    public function vessels()
    {
        return $this->hasMany(Vessel::class, 'default_port_id');
    }
}

