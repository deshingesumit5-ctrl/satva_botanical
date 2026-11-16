<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QcTest extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function getNameAttribute()
    {
        return $this->attributes['test_name'] ?? $this->attributes['name'] ?? '';
    }

    public function getCodeAttribute()
    {
        return $this->attributes['test_code'] ?? $this->attributes['code'] ?? '';
    }

    public function setNameAttribute($value)
    {
        $this->attributes['test_name'] = $value;
    }

    public function setCodeAttribute($value)
    {
        $this->attributes['test_code'] = $value;
    }

    public function recipeQcTests()
    {
        return $this->hasMany(RecipeQcTest::class);
    }

    public function getTestTypeAttribute()
    {
        return $this->attributes['test_category'] ?? null;
    }

    public function setTestTypeAttribute($value)
    {
        $this->attributes['test_category'] = $value;
    }
}
