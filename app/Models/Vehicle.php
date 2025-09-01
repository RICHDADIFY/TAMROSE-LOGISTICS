<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'label','type','make','model','year','plate_number',
        'capacity','active','notes'
    ];

    protected $appends = ['display_label'];

    public function getDisplayLabelAttribute()
    {
        // prefer label if set, else fall back to plate_number, else ID
        if ($this->label) return $this->label;
        if ($this->plate_number) return $this->plate_number;
        return "Vehicle #{$this->id}";
    }
    
    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($q)
    {
        return $q->where('active', true);
    }
}

