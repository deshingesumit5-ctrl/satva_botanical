<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryLot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'generated_qty' => 'decimal:4',
        'available_qty' => 'decimal:4',
        'reserved_qty' => 'decimal:4',
        'consumed_qty' => 'decimal:4',
        'sold_qty' => 'decimal:4',
        'remaining_qty' => 'decimal:4',
        'recovery_date' => 'date',
    ];

    public function sourceBatch()
    {
        return $this->belongsTo(Batch::class, 'source_batch_id');
    }

    public function sourceStage()
    {
        return $this->belongsTo(RecipeStage::class, 'source_stage_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function consumptions()
    {
        return $this->hasMany(RecoveryLotConsumption::class);
    }
}
