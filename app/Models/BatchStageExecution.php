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
        'actual_hold_time_minutes' => 'integer',
        'operator_count' => 'integer',
        'completed_at' => 'datetime',
        'supervisor_authorized_at' => 'datetime',
    ];

    public function authorizedByUser()
    {
        return $this->belongsTo(User::class, 'supervisor_authorized_by');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function recipeStage()
    {
        return $this->belongsTo(RecipeStage::class);
    }

    public function stage()
    {
        return $this->hasOneThrough(Stage::class, RecipeStage::class, 'id', 'id', 'recipe_stage_id', 'stage_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function activityExecutions()
    {
        return $this->hasMany(BatchActivityExecution::class)->orderBy('sequence_no');
    }

    public function activities()
    {
        return $this->activityExecutions();
    }

    public function parameterReadings()
    {
        return $this->hasMany(BatchParameterReading::class);
    }

    public function parameters()
    {
        return $this->parameterReadings();
    }

    public function outputs()
    {
        return $this->hasMany(BatchStageOutput::class);
    }

    public function getParameterLogsAttribute()
    {
        return $this->parameterReadings ?? collect();
    }

    public function getConsumptionsAttribute()
    {
        return $this->batch ? $this->batch->consumptions()->with(['material', 'uom'])->where('recipe_stage_id', $this->recipe_stage_id)->get() : collect();
    }

    public function getLineClearanceByAttribute()
    {
        return $this->actual_operator_name;
    }

    public function getLineClearanceDoneAttribute()
    {
        return !empty($this->equipment_id);
    }

    public function getOutputQuantityAttribute()
    {
        $saved = $this->outputs->whereIn('output_type', ['WIP', 'Finished'])->sum('actual_qty');
        if ($saved > 0) {
            return $saved;
        }
        return $this->recipeStage ? $this->recipeStage->outputMaterials->whereIn('output_type', ['WIP', 'Finished'])->sum('standard_qty') : 0;
    }

    public function getSolventRecoveredQtyAttribute()
    {
        $saved = $this->outputs->where('output_type', 'Recovered')->sum('actual_qty');
        if ($saved > 0) {
            return $saved;
        }
        return $this->recipeStage ? $this->recipeStage->outputMaterials->where('output_type', 'Recovered')->sum('standard_qty') : 0;
    }

    public function getSpentHerbQtyAttribute()
    {
        $saved = $this->outputs->whereIn('output_type', ['Spent', 'Waste'])->sum('actual_qty');
        if ($saved > 0) {
            return $saved;
        }
        return $this->recipeStage ? $this->recipeStage->outputMaterials->whereIn('output_type', ['Spent', 'Waste'])->sum('standard_qty') : 0;
    }

    public function qcRequest()
    {
        return $this->hasOne(QcRequest::class);
    }
}
