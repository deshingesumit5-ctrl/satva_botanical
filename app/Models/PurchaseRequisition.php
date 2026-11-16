<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequisition extends Model
{
    protected $guarded = [];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function getRemarksAttribute()
    {
        return $this->attributes['reason'] ?? null;
    }

    public function setRemarksAttribute($value)
    {
        $this->attributes['reason'] = $value;
    }

    public function getRequiredByDateAttribute()
    {
        if (array_key_exists('required_by_date', $this->attributes)) {
            return $this->attributes['required_by_date'];
        }

        if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
            return $this->items->first()->required_date ?? ($this->attributes['request_date'] ?? null);
        }

        $itemDate = $this->exists ? $this->items()->value('required_date') : null;

        return $itemDate ?? ($this->attributes['request_date'] ?? null);
    }

    public function getPlantIdAttribute()
    {
        return $this->department?->plant_id ?? $this->plant?->id;
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function plant()
    {
        return $this->hasOneThrough(Plant::class, Department::class, 'id', 'id', 'department_id', 'plant_id');
    }

    public function requestedByUser()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequisitionItem::class);
    }
}
