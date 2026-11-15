<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'planned_qty' => 'decimal:4',
        'planned_start_date' => 'date',
        'required_completion_date' => 'date',
        'approved_at' => 'datetime',
    ];

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

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function getPlannedQuantityAttribute()
    {
        return $this->planned_qty;
    }

    public function getRequirementsAttribute()
    {
        $requirements = collect();
        $version = $this->recipeVersion ?? $this->recipe?->currentVersion;
        if ($version) {
            $batchSize = (float)($version->standard_batch_size ?? 1000);
            $ratio = $batchSize > 0 ? ((float)$this->planned_qty / $batchSize) : 1;
            $stages = $version->stages()->with(['inputMaterials.material.baseUom'])->get();
            foreach ($stages as $stage) {
                foreach ($stage->inputMaterials as $input) {
                    $reqQty = (float)$input->quantity * $ratio;
                    $available = (float)StockLot::where('material_id', $input->material_id)
                        ->where('status', 'Active')
                        ->where(function ($q) {
                            $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now());
                        })
                        ->sum('available_qty');
                    $requirements->push((object)[
                        'material_id' => $input->material_id,
                        'material' => $input->material,
                        'required_qty' => round($reqQty, 4),
                        'available_qty' => round($available, 4),
                        'uom' => $input->material?->baseUom,
                    ]);
                }
            }
        }
        return $requirements;
    }
}
