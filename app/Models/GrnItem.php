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

    public function stockLot()
    {
        return $this->hasOne(StockLot::class, 'grn_item_id');
    }
}
