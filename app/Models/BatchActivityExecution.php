<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchActivityExecution extends Model
{
    protected $guarded = [];

    protected $casts = [
        'sequence_no' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'actual_duration_minutes' => 'integer',
    ];

    public function stageExecution()
    {
        return $this->belongsTo(BatchStageExecution::class, 'batch_stage_execution_id');
    }

    public function recipeStageActivity()
    {
        return $this->belongsTo(RecipeStageActivity::class);
    }
}
