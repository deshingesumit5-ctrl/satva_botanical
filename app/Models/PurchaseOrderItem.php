<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function getPendingQtyAttribute()
    {
        return max(0, (float)$this->quantity - (float)$this->received_qty);
    }

    public function getOrderedQtyAttribute()
    {
        return $this->attributes['quantity'] ?? null;
    }

    public function setOrderedQtyAttribute($value)
    {
        $this->attributes['quantity'] = $value;
    }

    public function getReceivedQuantityAttribute()
    {
        return $this->attributes['received_qty'] ?? 0;
    }

    public function setReceivedQuantityAttribute($value)
    {
        $this->attributes['received_qty'] = $value;
    }

    public function getUnitPriceAttribute()
    {
        return $this->attributes['rate'] ?? 0;
    }

    public function setUnitPriceAttribute($value)
    {
        $this->attributes['rate'] = $value;
    }

    public function getTaxPercentAttribute()
    {
        return $this->attributes['gst_rate'] ?? 0;
    }

    public function setTaxPercentAttribute($value)
    {
        $this->attributes['gst_rate'] = $value;
    }
}
