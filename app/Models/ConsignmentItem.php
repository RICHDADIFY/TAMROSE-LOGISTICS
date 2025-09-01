<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsignmentItem extends Model
{
    protected $fillable = [
        'consignment_id','description','quantity','unit','photos_json','note',
    ];

    protected $casts = [
        'photos_json' => 'array',
    ];

    public function consignment()
    {
        return $this->belongsTo(Consignment::class);
    }
}
