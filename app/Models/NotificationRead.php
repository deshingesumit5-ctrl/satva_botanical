<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
