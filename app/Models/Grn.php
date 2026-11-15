<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grn extends Model
{
    protected $guarded = [];

    protected $casts = [
        'receipt_date' => 'date',
        'invoice_date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id')->withDefault(['name' => 'Main Extraction Plant']);
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(GrnItem::class);
    }

    public function stockLots()
    {
        return $this->hasMany(StockLot::class, 'grn_id');
    }
}
