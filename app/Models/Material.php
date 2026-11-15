<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'lot_tracking' => 'boolean',
        'qc_required' => 'boolean',
        'conversion_factor' => 'decimal:4',
        'min_stock' => 'decimal:4',
    ];

    public function baseUom()
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    public function uom()
    {
        return $this->baseUom();
    }

    public function alternateUom()
    {
        return $this->belongsTo(Uom::class, 'alternate_uom_id');
    }

    public function stockLots()
    {
        return $this->hasMany(StockLot::class);
    }
}
