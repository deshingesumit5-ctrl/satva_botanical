<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReworkRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'additional_duration_minutes' => 'integer',
        'additional_material_cost' => 'decimal:2',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function originalStageExecution()
    {
        return $this->belongsTo(BatchStageExecution::class, 'original_stage_execution_id');
    }

    public function reworkStageExecution()
    {
        return $this->belongsTo(BatchStageExecution::class, 'rework_stage_execution_id');
    }

    public function qcRequest()
    {
        return $this->belongsTo(QcRequest::class);
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
