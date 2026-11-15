<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeStageActivity extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sequence_no' => 'integer',
        'is_mandatory' => 'boolean',
        'standard_duration_minutes' => 'integer',
        'start_end_time_required' => 'boolean',
    ];

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }
}
