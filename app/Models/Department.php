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
}
