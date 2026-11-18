<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispatch extends Model
{
    protected $guarded = [];

    protected $casts = [
        'dispatch_date' => 'date',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(DispatchItem::class);
    }

    public function salesInvoice()
    {
        return $this->hasOne(SalesInvoice::class);
    }

    public function getTransporterNameAttribute()
    {
        return $this->attributes['transport_details'] ?? null;
    }
}
