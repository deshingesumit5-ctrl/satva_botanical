<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Stage extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'activity_required' => 'boolean',
        'material_input_required' => 'boolean',
        'material_output_required' => 'boolean',
        'qc_available' => 'boolean',
        'default_duration' => 'integer',
    ];

    public function getNameAttribute()
    {
        return $this->attributes['stage_name'] ?? $this->attributes['name'] ?? '';
    }

    public function getCodeAttribute()
    {
        return $this->attributes['stage_code'] ?? $this->attributes['code'] ?? '';
    }

    public function setNameAttribute($value)
    {
        $this->attributes['stage_name'] = $value;
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['stage_code'] = $value;
    }

    public function defaultEquipment()
    {
        return $this->belongsTo(Equipment::class, 'default_equipment_id');
    }
}
