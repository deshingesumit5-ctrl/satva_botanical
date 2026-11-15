<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockLot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'received_qty' => 'decimal:4',
        'reserved_qty' => 'decimal:4',
        'consumed_qty' => 'decimal:4',
        'returned_qty' => 'decimal:4',
        'available_qty' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'mfg_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function storageLocation()
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function ledgerEntries()
    {
        return $this->hasMany(StockLedger::class);
    }
}
