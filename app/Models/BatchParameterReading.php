<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchParameterReading extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_within_range' => 'boolean',
        'entered_at' => 'datetime',
    ];

    public function stageExecution()
    {
        return $this->belongsTo(BatchStageExecution::class, 'batch_stage_execution_id');
    }

    public function recipeParameter()
    {
        return $this->belongsTo(RecipeParameter::class);
    }

    public function enteredByUser()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
