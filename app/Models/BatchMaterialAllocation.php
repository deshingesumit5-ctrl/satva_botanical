<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchMaterialAllocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'allocated_qty' => 'decimal:4',
        'allocated_at' => 'datetime',
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

    public function allocatedByUser()
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }
}
