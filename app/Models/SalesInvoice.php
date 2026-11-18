<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'taxable_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function dispatch()
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function items()
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function receipts()
    {
        return $this->hasMany(CustomerReceipt::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusAttribute()
    {
        return $this->attributes['payment_status'] ?? 'Unpaid';
    }

    public function getSubtotalAttribute()
    {
        return $this->attributes['taxable_amount'] ?? 0;
    }

    public function getTaxAmountAttribute(): float
    {
        return (float) ($this->attributes['cgst_amount'] ?? 0)
            + (float) ($this->attributes['sgst_amount'] ?? 0)
            + (float) ($this->attributes['igst_amount'] ?? 0);
    }

    public function getGrandTotalAttribute()
    {
        return $this->attributes['total_amount'] ?? 0;
    }
}
