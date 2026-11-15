<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecoveryLotConsumption extends Model
{
    protected $guarded = [];

    protected $casts = [
        'consumed_qty' => 'decimal:4',
        'consumed_date' => 'date',
    ];

    public function recoveryLot()
    {
        return $this->belongsTo(RecoveryLot::class);
    }

    public function consumingBatch()
    {
        return $this->belongsTo(Batch::class, 'consuming_batch_id');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
