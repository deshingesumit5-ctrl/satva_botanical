<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'start_time_required' => 'boolean',
        'end_time_required' => 'boolean',
        'operator_entry_required' => 'boolean',
        'default_duration' => 'integer',
    ];

    public function recipeStageActivities()
    {
        return $this->hasMany(RecipeStageActivity::class);
    }
}
