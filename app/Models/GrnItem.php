<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'po_qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'accepted_qty' => 'decimal:4',
        'rejected_qty' => 'decimal:4',
        'shortage_excess' => 'decimal:4',
        'rate' => 'decimal:2',
        'qc_required' => 'boolean',
        'mfg_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class, 'uom_id')->withDefault(function() {
            return $this->material ? $this->material->uom : Uom::first();
        });
    }

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class, 'storage_location_id')->withDefault();
    }

    public function stockLot()
    {
        return $this->hasOne(StockLot::class, 'grn_item_id');
    }

    public function getReceivedQuantityAttribute()
    {
        return $this->attributes['received_qty'] ?? 0;
    }

    public function getAcceptedQuantityAttribute()
    {
        return $this->attributes['accepted_qty'] ?? 0;
    }

    public function getRejectedQuantityAttribute()
    {
        return $this->attributes['rejected_qty'] ?? 0;
    }

    public function getVendorLotNumberAttribute()
    {
        return $this->attributes['remarks'] ?? $this->attributes['lot_number'] ?? null;
    }

    public function getStockLotIdAttribute()
    {
        return $this->stockLot?->id;
    }
}
