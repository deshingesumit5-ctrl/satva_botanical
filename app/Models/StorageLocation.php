<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageLocation extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function stockLots()
    {
        return $this->hasMany(StockLot::class);
    }

    public function getCodeAttribute()
    {
        return $this->attributes['location_code'] ?? null;
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['location_code'] = $value;
    }

    public function getZoneAttribute()
    {
        return trim(($this->rack_no ?? '') . ' ' . ($this->bin_no ?? '')) ?: null;
    }
}
