<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'planned_qty' => 'decimal:4',
        'planned_start_date' => 'date',
        'required_completion_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function recipeVersion()
    {
        return $this->belongsTo(RecipeVersion::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }
}
