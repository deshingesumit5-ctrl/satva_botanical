<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoiceItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'rate' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'gst_rate' => 'decimal:2',
        'gst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function getUnitPriceAttribute()
    {
        return $this->attributes['rate'] ?? 0;
    }

    public function getTaxPercentAttribute()
    {
        return $this->attributes['gst_rate'] ?? 0;
    }

    public function getLineTotalAttribute()
    {
        return $this->attributes['total_amount'] ?? 0;
    }
}
