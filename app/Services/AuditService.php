<?php

namespace App\Services;

use App\Models\AuditTrail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    public static function log(string $module, string $action, ?string $recordReference = null, $oldValue = null, $newValue = null, ?string $reason = null): AuditTrail
    {
        $user = Auth::user();
        
        return AuditTrail::create([
            'module' => $module,
            'record_reference' => $recordReference,
            'action' => $action,
            'old_value' => is_string($oldValue) ? $oldValue : ($oldValue ? json_encode($oldValue) : null),
            'new_value' => is_string($newValue) ? $newValue : ($newValue ? json_encode($newValue) : null),
            'user_id' => $user?->id,
            'user_name' => $user ? ($user->name . ' (' . $user->username . ')') : 'System',
            'ip_address' => Request::ip(),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
