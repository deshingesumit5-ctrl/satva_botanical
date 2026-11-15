<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerReceipt extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'receipt_date' => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivedByUser()
    {
        return $this->createdByUser();
    }
}
