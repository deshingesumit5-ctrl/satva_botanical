<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'request_date' => 'datetime',
        'decision_at' => 'datetime',
        'attachments' => 'array',
        'readings_snapshot' => 'array',
        'outputs_snapshot' => 'array',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recipeVersion()
    {
        return $this->belongsTo(RecipeVersion::class);
    }

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function stageExecution()
    {
        return $this->belongsTo(BatchStageExecution::class, 'batch_stage_execution_id');
    }

    public function parentRequest()
    {
        return $this->belongsTo(QcRequest::class, 'parent_qc_request_id');
    }

    public function retestRequest()
    {
        return $this->belongsTo(QcRequest::class, 'retest_qc_request_id');
    }

    public function reworkRecords()
    {
        return $this->hasMany(ReworkRecord::class, 'qc_request_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id')->withDefault();
    }

    public function submittedByUser()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function decisionByUser()
    {
        return $this->belongsTo(User::class, 'decision_by');
    }

    public function results()
    {
        return $this->hasMany(QcRequestResult::class);
    }
}
