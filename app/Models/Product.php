<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'batch_tracking' => 'boolean',
        'manufacturing_date_required' => 'boolean',
        'expiry_date_required' => 'boolean',
        'qc_required' => 'boolean',
        'is_saleable' => 'boolean',
        'conversion_factor' => 'decimal:4',
        'min_stock' => 'decimal:4',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function baseUom()
    {
        return $this->belongsTo(Uom::class, 'base_uom_id');
    }

    public function alternateUom()
    {
        return $this->belongsTo(Uom::class, 'alternate_uom_id');
    }

    public function recipes()
    {
        return $this->hasMany(Recipe::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function salesOrderItems()
    {
        return $this->hasMany(SalesOrderItem::class);
    }
}
