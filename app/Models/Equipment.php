<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use SoftDeletes;
    protected $table = 'equipments';
    protected $guarded = [];

    protected $casts = [
        'capacity' => 'decimal:2',
        'calibration_required' => 'boolean',
        'last_calibration_date' => 'date',
        'next_calibration_date' => 'date',
        'maintenance_due_date' => 'date',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function capacityUom()
    {
        return $this->belongsTo(Uom::class, 'capacity_uom_id');
    }
}
