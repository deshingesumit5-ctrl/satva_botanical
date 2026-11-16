<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'role_code',
        'role_name',
        'description',
        'status',
    ];

    public function getNameAttribute(): ?string
    {
        return $this->attributes['role_name'] ?? null;
    }

    public function setNameAttribute($value): void
    {
        $this->attributes['role_name'] = $value;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->attributes['role_code'] ?? null;
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['role_code'] = $value;
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function hasPermissionTo(string $code): bool
    {
        if ($this->role_code === 'super_admin' || $this->role_code === 'Super Admin') {
            return true;
        }

        return $this->permissions()->where('code', $code)->exists();
    }
}
