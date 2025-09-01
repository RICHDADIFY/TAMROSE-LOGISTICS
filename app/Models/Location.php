<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = [
        'name','type','address','lat','lng',
        'contact_name','contact_phone','contact_email','active'
    ];
}

