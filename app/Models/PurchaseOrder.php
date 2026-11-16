<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function requisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class, 'plant_id')->withDefault(['name' => 'Main Extraction Plant']);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }

    public function getDeliveryTermsAttribute()
    {
        return $this->attributes['terms_conditions'] ?? null;
    }

    public function setDeliveryTermsAttribute($value)
    {
        $this->attributes['terms_conditions'] = $value;
    }

    public function getRemarksAttribute()
    {
        return $this->attributes['terms_conditions'] ?? null;
    }

    public function setRemarksAttribute($value)
    {
        $this->attributes['terms_conditions'] = $value;
    }

    public function getPlantIdAttribute()
    {
        return $this->requisition?->plant_id ?? Plant::where('status', 'Active')->value('id');
    }
}
