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

    public function defaultEquipment()
    {
        return $this->belongsTo(Equipment::class, 'default_equipment_id');
    }
}
