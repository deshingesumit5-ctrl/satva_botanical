<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $guarded = [];

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
