<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchHold extends Model
{
    protected $guarded = [];

    protected $casts = [
        'held_at' => 'datetime',
        'resumed_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function batch()
    {
        return $this->belongsTo(Batch::class);
    }

    public function heldByUser()
    {
        return $this->belongsTo(User::class, 'held_by');
    }

    public function resumedByUser()
    {
        return $this->belongsTo(User::class, 'resumed_by');
    }
}
