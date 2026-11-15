<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentNumbering extends Model
{
    protected $guarded = [];

    protected $casts = [
        'financial_year_reset' => 'boolean',
        'starting_number' => 'integer',
        'current_number' => 'integer',
    ];
}
