<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'taxable_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'other_charges' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function grn()
    {
        return $this->belongsTo(Grn::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getBillNumberAttribute()
    {
        return $this->attributes['internal_invoice_number'] ?? null;
    }

    public function getInvoiceNumberAttribute()
    {
        return $this->attributes['supplier_invoice_number'] ?? null;
    }

    public function getStatusAttribute()
    {
        return $this->attributes['payment_status'] ?? 'Unpaid';
    }

    public function getSubtotalAttribute()
    {
        return $this->attributes['taxable_amount'] ?? 0;
    }

    public function getGrandTotalAttribute()
    {
        return $this->attributes['total_amount'] ?? 0;
    }
}
