<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyExpense extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'date',
        'posted_at' => 'datetime',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function enteredByUser()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
