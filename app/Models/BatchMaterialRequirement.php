<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchMaterialRequirement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'planned_qty' => 'decimal:4',
        'allocated_qty' => 'decimal:4',
        'consumed_qty' => 'decimal:4',
        'returned_qty' => 'decimal:4',
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

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function getPendingQtyAttribute()
    {
        return max(0, (float)$this->planned_qty - (float)$this->allocated_qty);
    }
}
