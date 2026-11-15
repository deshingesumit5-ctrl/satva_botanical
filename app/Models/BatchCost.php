<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchCost extends Model
{
    protected $guarded = [];

    protected $casts = [
        'direct_material_cost' => 'decimal:2',
        'labour_cost' => 'decimal:2',
        'electricity_cost' => 'decimal:2',
        'maintenance_cost' => 'decimal:2',
        'equipment_cost' => 'decimal:2',
        'misc_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_batch_cost' => 'decimal:2',
        'finished_qty' => 'decimal:4',
        'cost_per_unit' => 'decimal:4',
        'calculated_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
