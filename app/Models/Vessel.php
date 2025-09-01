<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    protected $fillable = [
        'name','default_port_id','contact_name','contact_phone','contact_email','active'
    ];

    public function defaultPort()
    {
        return $this->belongsTo(Port::class, 'default_port_id');
    }
}

