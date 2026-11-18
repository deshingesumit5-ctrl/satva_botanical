<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class);
    }

    public function isReadBy(?User $user): bool
    {
        if ($this->is_read) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($this->relationLoaded('reads')) {
            return $this->reads->contains('user_id', $user->id);
        }

        return $this->reads()->where('user_id', $user->id)->exists();
    }
}
