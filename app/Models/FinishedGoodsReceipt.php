<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishedGoodsReceipt extends Model
{
    protected $guarded = [];

    protected $casts = [
        'actual_qty' => 'decimal:4',
        'mfg_date' => 'date',
        'expiry_date' => 'date',
        'receipt_date' => 'date',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
