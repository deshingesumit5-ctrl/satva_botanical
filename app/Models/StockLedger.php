<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class StockLedger extends Model
{
    protected $table = 'stock_ledger';
    protected $guarded = [];

    protected $casts = [
        'transaction_date' => 'datetime',
        'inward_qty' => 'decimal:4',
        'outward_qty' => 'decimal:4',
        'balance_qty' => 'decimal:4',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            throw new RuntimeException('Stock ledger records are immutable and cannot be updated.');
        });

        static::deleting(function ($model) {
            throw new RuntimeException('Stock ledger records are immutable and cannot be deleted.');
        });
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLot()
    {
        return $this->belongsTo(StockLot::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
