<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseAllocation extends Model
{
    protected $guarded = [];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'total_expense' => 'decimal:2',
        'eligible_batch_count' => 'integer',
        'allocated_amount' => 'decimal:2',
        'allocation_date' => 'date',
    ];

    public function postedByUser()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function allocatedByUser()
    {
        return $this->postedByUser();
    }

    public function items()
    {
        return $this->hasMany(ExpenseAllocationItem::class);
    }
}
