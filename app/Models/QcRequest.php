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
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function stageExecution()
    {
        return $this->belongsTo(BatchStageExecution::class, 'batch_stage_execution_id');
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
