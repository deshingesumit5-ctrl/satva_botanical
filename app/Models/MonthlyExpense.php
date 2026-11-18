<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyExpense extends Model
{
    public const CATEGORIES = [
        'Electricity' => 'Electricity (HT Power / Substation)',
        'Labour' => 'Labour (Direct Plant Labor)',
        'Equipment-related' => 'Equipment-related (Machinery & Tooling)',
        'Maintenance' => 'Maintenance (Plant & Boiler Upkeep)',
        'Miscellaneous' => 'Miscellaneous (QC & General Overheads)',
        'Other Manufacturing Expenses' => 'Other Manufacturing Expenses',
    ];

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'entry_date' => 'date',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
        'month' => 'integer',
        'year' => 'integer',
    ];

    public function enteredByUser()
    {
        return $this->belongsTo(User::class, 'entered_by');
    }

    public function createdByUser()
    {
        return $this->enteredByUser();
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id')->withDefault(['name' => 'All Plants']);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
