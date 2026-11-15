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

    public function getNameAttribute()
    {
        return $this->attributes['uom_name'] ?? $this->attributes['name'] ?? '';
    }

    public function getCodeAttribute()
    {
        return $this->attributes['uom_code'] ?? $this->attributes['code'] ?? '';
    }

    public function getSymbolAttribute()
    {
        return $this->attributes['uom_code'] ?? $this->attributes['symbol'] ?? '';
    }

    public function setNameAttribute($value)
    {
        $this->attributes['uom_name'] = $value;
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['uom_code'] = $value;
    }

    public function setSymbolAttribute($value)
    {
        $this->attributes['uom_code'] = $value;
    }

    public function baseUom()
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }
}
