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
        if (in_array(strtolower((string) $this->role_code), ['super_admin', 'super admin'], true) ||
            strtolower((string) $this->role_name) === 'super admin') {
            return true;
        }

        if ($this->status !== 'Active') {
            return false;
        }

        $codesToCheck = [$code];

        // Alias support for Authorize Stage
        if (in_array(strtolower($code), ['authorize stage', 'authorize_stage', 'production.authorize_stage', 'production.execution.authorize'], true)) {
            $codesToCheck = array_merge($codesToCheck, [
                'production.authorize_stage.approve',
                'production.authorize_stage.view',
                'production.authorize_stage.override',
                'production.execution.approve',
                'production.execution.override',
            ]);
        }

        // Alias support for .create <-> .add and 2-part module.screen keys (defaults to .view)
        $parts = explode('.', $code);
        if (count($parts) === 3) {
            if ($parts[2] === 'create') {
                $codesToCheck[] = "{$parts[0]}.{$parts[1]}.add";
            } elseif ($parts[2] === 'add') {
                $codesToCheck[] = "{$parts[0]}.{$parts[1]}.create";
            }
            // sales.* <-> commercial.* alias
            if ($parts[0] === 'sales') {
                $scr = $parts[1] === 'invoices' ? 'sales_invoices' : str_replace('-', '_', $parts[1]);
                $codesToCheck[] = "commercial.{$scr}.{$parts[2]}";
            }
        } elseif (count($parts) === 2) {
            $codesToCheck[] = "{$parts[0]}.{$parts[1]}.view";
        }

        return $this->permissions()->whereIn('code', array_unique($codesToCheck))->exists();
    }
}
