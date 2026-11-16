<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionItem extends Model
{
    protected $guarded = [];

    protected $casts = [
        'required_qty' => 'decimal:4',
        'required_date' => 'date',
    ];

    public function getQuantityAttribute()
    {
        return $this->attributes['required_qty'] ?? null;
    }

    public function setQuantityAttribute($value)
    {
        $this->attributes['required_qty'] = $value;
    }

    public function requisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }
}
