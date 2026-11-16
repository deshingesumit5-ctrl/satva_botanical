<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parameter extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    protected $casts = [
        'decimal_precision' => 'integer',
        'options' => 'array',
    ];

    public function recipeParameters()
    {
        return $this->hasMany(RecipeParameter::class);
    }

    public function getDefaultUomAttribute()
    {
        return $this->attributes['uom'] ?? null;
    }

    public function setDefaultUomAttribute($value)
    {
        $this->attributes['uom'] = $value;
    }
}
