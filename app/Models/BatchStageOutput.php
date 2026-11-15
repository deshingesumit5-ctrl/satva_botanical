<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchStageOutput extends Model
{
    protected $guarded = [];

    protected $casts = [
        'actual_qty' => 'decimal:4',
        'qc_required' => 'boolean',
    ];

    public function stageExecution()
    {
        return $this->belongsTo(BatchStageExecution::class, 'batch_stage_execution_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }
}
