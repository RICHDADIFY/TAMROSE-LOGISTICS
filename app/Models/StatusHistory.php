<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusHistory extends Model
{
    protected $fillable = ['from_status','to_status','changed_by','note'];

    public function subject() { return $this->morphTo(); }
    public function user()    { return $this->belongsTo(User::class,'changed_by'); }
}
