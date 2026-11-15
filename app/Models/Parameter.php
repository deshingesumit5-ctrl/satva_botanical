<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parameter extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'decimal_precision' => 'integer',
        'options' => 'array',
    ];
}
