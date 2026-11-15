<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uom extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'decimal_precision' => 'integer',
        'conversion_factor' => 'decimal:4',
        'is_stock_unit' => 'boolean',
    ];

    public function baseUom()
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }
}
