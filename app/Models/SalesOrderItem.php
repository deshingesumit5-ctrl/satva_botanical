<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'dispatched_qty' => 'decimal:4',
        'rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function stockLot()
    {
        return $this->belongsTo(StockLot::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function getPendingQtyAttribute()
    {
        return max(0, (float)$this->quantity - (float)$this->dispatched_qty);
    }
}
