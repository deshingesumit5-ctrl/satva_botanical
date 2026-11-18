<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchCost extends Model
{
    protected $guarded = [];

    protected $casts = [
        'direct_material_cost' => 'decimal:2',
        'labour_cost' => 'decimal:2',
        'electricity_cost' => 'decimal:2',
        'maintenance_cost' => 'decimal:2',
        'equipment_cost' => 'decimal:2',
        'misc_cost' => 'decimal:2',
        'other_cost' => 'decimal:2',
        'total_batch_cost' => 'decimal:2',
        'finished_qty' => 'decimal:4',
        'cost_per_unit' => 'decimal:4',
        'calculated_at' => 'datetime',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Compute solvent consumption cost from the linked batch consumptions.
     */
    public function getSolventCostAttribute(): float
    {
        $batch = $this->batch;
        if (!$batch) {
            return 0.0;
        }

        $consumptions = $batch->relationLoaded('consumptions')
            ? $batch->consumptions
            : $batch->consumptions()->with(['material', 'stockLot'])->get();

        if ($consumptions->isEmpty()) {
            return 0.0;
        }

        $stockService = app(\App\Services\StockService::class);
        $solventCost = 0.0;

        foreach ($consumptions as $c) {
            if ($c->material && $c->material->material_category === 'Solvent') {
                $unitCost = $c->stockLot ? $stockService->getLotValuationCost($c->stockLot->id) : 0.0;
                $netQty = (float)$c->net_consumed_qty > 0
                    ? (float)$c->net_consumed_qty
                    : ((float)$c->actual_consumed_qty - (float)$c->returned_qty);
                $solventCost += ($netQty * $unitCost);
            }
        }

        return round(min($solventCost, (float)$this->direct_material_cost), 2);
    }

    /**
     * Non-solvent direct material cost (raw herbs, chemicals, water, consumables).
     */
    public function getRawMaterialCostAttribute(): float
    {
        return max(0.0, round((float)$this->direct_material_cost - $this->solvent_cost, 2));
    }

    /**
     * Total non-labour overhead costs (electricity, maintenance, equipment, misc, other).
     */
    public function getOverheadCostAttribute(): float
    {
        return round(
            (float)$this->electricity_cost
            + (float)$this->maintenance_cost
            + (float)$this->equipment_cost
            + (float)$this->misc_cost
            + (float)$this->other_cost,
            2
        );
    }

    public function getUtilityCostAttribute(): float
    {
        return $this->overhead_cost;
    }

    public function getLaborCostAttribute(): float
    {
        return (float)$this->labour_cost;
    }

    public function getTotalCostAttribute(): float
    {
        return (float)$this->total_batch_cost;
    }
}
