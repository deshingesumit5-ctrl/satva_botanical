<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchReconciliation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'total_input_qty' => 'decimal:4',
        'raw_material_qty' => 'decimal:4',
        'water_qty' => 'decimal:4',
        'chemical_qty' => 'decimal:4',
        'solvent_qty' => 'decimal:4',
        'other_input_qty' => 'decimal:4',
        'total_output_qty' => 'decimal:4',
        'fg_qty' => 'decimal:4',
        'wip_qty' => 'decimal:4',
        'cake_qty' => 'decimal:4',
        'liquid_qty' => 'decimal:4',
        'recovered_solvent_qty' => 'decimal:4',
        'by_product_qty' => 'decimal:4',
        'spent_qty' => 'decimal:4',
        'waste_qty' => 'decimal:4',
        'material_returns_qty' => 'decimal:4',
        'unaccounted_difference_qty' => 'decimal:4',
        'reconciled_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function reconciledByUser()
    {
        return $this->belongsTo(User::class, 'reconciled_by');
    }
}
