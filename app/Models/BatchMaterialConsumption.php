<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchMaterialConsumption extends Model
{
    protected $guarded = [];

    protected $casts = [
        'allocated_qty' => 'decimal:4',
        'actual_consumed_qty' => 'decimal:4',
        'returned_qty' => 'decimal:4',
        'net_consumed_qty' => 'decimal:4',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function stockLot()
    {
        return $this->belongsTo(StockLot::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function enteredByUser()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
