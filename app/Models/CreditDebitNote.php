<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditDebitNote extends Model
{
    protected $guarded = [];

    protected $casts = [
        'note_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
