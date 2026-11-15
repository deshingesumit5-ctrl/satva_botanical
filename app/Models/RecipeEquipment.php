<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeEquipment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
