<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
    ];

    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function salesOrderItem()
    {
        return $this->belongsTo(SalesOrderItem::class);
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
}
