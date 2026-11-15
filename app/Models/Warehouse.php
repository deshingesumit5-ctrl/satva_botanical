<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function storageLocations()
    {
        return $this->hasMany(StorageLocation::class);
    }

    public function stockLots()
    {
        return $this->hasMany(StockLot::class);
    }
}
