<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipeVersion extends Model
{
    protected $guarded = [];

    protected $casts = [
        'reference_batch_size' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'approved_at' => 'datetime',
    ];

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function batchUom()
    {
        return $this->belongsTo(Uom::class, 'batch_uom_id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function stages()
    {
        return $this->hasMany(RecipeStage::class)->orderBy('sequence_no');
    }
}
