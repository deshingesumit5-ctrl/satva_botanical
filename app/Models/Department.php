<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;
    protected $guarded = [];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function getHeadOfDepartmentAttribute()
    {
        return $this->attributes['department_head'] ?? null;
    }

    public function setHeadOfDepartmentAttribute($value)
    {
        $this->attributes['department_head'] = $value;
    }
}
