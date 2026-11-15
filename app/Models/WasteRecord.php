<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WasteRecord extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'disposal_date' => 'date',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function stage()
    {
        return $this->belongsTo(RecipeStage::class, 'stage_id');
    }

    public function uom()
    {
        return $this->belongsTo(Uom::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
