<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function currentVersion()
    {
        return $this->belongsTo(RecipeVersion::class, 'current_version_id');
    }

    public function versions()
    {
        return $this->hasMany(RecipeVersion::class);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function productionPlans()
    {
        return $this->hasMany(ProductionPlan::class);
    }
}
