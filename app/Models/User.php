<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class, 'user_id');
    }

    public function unreadNotifications()
    {
        return $this->hasMany(\App\Models\Notification::class, 'user_id')->where('is_read', false);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role && in_array(strtolower($this->role->role_code), ['super_admin', 'super admin']);
    }

    public function hasPermission(string $code): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        return $this->role->hasPermissionTo($code);
    }

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    public function getEmployeeCodeAttribute()
    {
        return $this->attributes['user_id'] ?? null;
    }

    public function setEmployeeCodeAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['user_id'] = $value;
        }
    }
}
