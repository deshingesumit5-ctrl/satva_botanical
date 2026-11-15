<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeMaterial extends Model
{
    protected $guarded = [];

    protected $casts = [
        'standard_qty' => 'decimal:4',
        'percentage_basis' => 'decimal:2',
        'is_mandatory' => 'boolean',
        'lot_tracking' => 'boolean',
        'multiple_lot_support' => 'boolean',
        'is_reusable' => 'boolean',
        'is_saleable' => 'boolean',
        'qc_required' => 'boolean',
    ];

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }
}
