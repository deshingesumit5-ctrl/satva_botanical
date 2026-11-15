<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'credit_limit' => 'decimal:2',
    ];

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesInvoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function invoices()
    {
        return $this->hasMany(SalesInvoice::class);
    }

    public function receipts()
    {
        return $this->hasMany(CustomerReceipt::class);
    }
}
