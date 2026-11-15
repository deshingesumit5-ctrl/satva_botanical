<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseAllocationItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function allocation()
    {
        return $this->belongsTo(ExpenseAllocation::class, 'expense_allocation_id');
    }

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }
}
