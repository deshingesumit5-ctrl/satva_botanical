<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class AuditTrail extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            throw new RuntimeException('Audit trail records are immutable and cannot be updated.');
        });

        static::deleting(function ($model) {
            throw new RuntimeException('Audit trail records are immutable and cannot be deleted.');
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
