<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeParameter extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_mandatory' => 'boolean',
        'display_sequence' => 'integer',
    ];

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function recipeStageActivity()
    {
        return $this->belongsTo(RecipeStageActivity::class);
    }

    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }
}
