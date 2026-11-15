<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchStageExecution extends Model
{
    protected $guarded = [];

    protected $casts = [
        'execution_number' => 'integer',
        'sequence_no' => 'integer',
        'occurrence_no' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
        'operator_count' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function activityExecutions()
    {
        return $this->hasMany(BatchActivityExecution::class)->orderBy('sequence_no');
    }

    public function parameterReadings()
    {
        return $this->hasMany(BatchParameterReading::class);
    }

    public function outputs()
    {
        return $this->hasMany(BatchStageOutput::class);
    }

    public function qcRequest()
    {
        return $this->hasOne(QcRequest::class);
    }
}
