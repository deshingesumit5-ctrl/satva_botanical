<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WipRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'generated_qty' => 'decimal:4',
        'consumed_qty' => 'decimal:4',
        'available_qty' => 'decimal:4',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function sourceStage()
    {
        return $this->belongsTo(RecipeStage::class, 'source_stage_id');
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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
