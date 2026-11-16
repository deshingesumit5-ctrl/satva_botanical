<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'supplied_material_categories' => 'array',
    ];

    public function getSupplierCodeAttribute()
    {
        return $this->attributes['code'] ?? null;
    }

    public function setSupplierCodeAttribute($value)
    {
        $this->attributes['code'] = $value;
    }

    public function getAddressLineAttribute()
    {
        return $this->attributes['address'] ?? null;
    }

    public function setAddressLineAttribute($value)
    {
        $this->attributes['address'] = $value;
    }

    public function getPaymentTermsDaysAttribute()
    {
        return $this->attributes['payment_terms'] ?? null;
    }

    public function setPaymentTermsDaysAttribute($value)
    {
        $this->attributes['payment_terms'] = $value;
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function grns()
    {
        return $this->hasMany(Grn::class);
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
