<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Batch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'planned_qty' => 'decimal:4',
        'actual_qty' => 'decimal:4',
        'production_date' => 'date',
        'planned_start_date' => 'date',
        'planned_end_date' => 'date',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'operator_count' => 'integer',
    ];

    public function productionPlan()
    {
        return $this->belongsTo(ProductionPlan::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function recipeVersion()
    {
        return $this->belongsTo(RecipeVersion::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function holds()
    {
        return $this->hasMany(BatchHold::class);
    }

    public function requirements()
    {
        return $this->hasMany(BatchMaterialRequirement::class);
    }

    public function materialRequirements()
    {
        return $this->requirements();
    }

    public function allocations()
    {
        return $this->hasMany(BatchMaterialAllocation::class);
    }

    public function materialAllocations()
    {
        return $this->allocations();
    }

    public function consumptions()
    {
        return $this->hasMany(BatchMaterialConsumption::class);
    }

    public function materialConsumptions()
    {
        return $this->consumptions();
    }

    public function stageExecutions()
    {
        return $this->hasMany(BatchStageExecution::class)->orderBy('sequence_no')->orderBy('execution_number');
    }

    public function qcRequests()
    {
        return $this->hasMany(QcRequest::class);
    }

    public function reworks()
    {
        return $this->hasMany(ReworkRecord::class);
    }

    public function reconciliation()
    {
        return $this->hasOne(BatchReconciliation::class);
    }

    public function fgReceipts()
    {
        return $this->hasMany(FinishedGoodsReceipt::class);
    }

    public function cost()
    {
        return $this->hasOne(BatchCost::class);
    }
}
