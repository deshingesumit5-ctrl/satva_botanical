<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemSettingController extends Controller
{
    public function edit()
    {
        $system = SystemSetting::firstOrCreate(['id' => 1]);
        return view('settings.system', compact('system'));
    }

    public function update(Request $request)
    {
        $system = SystemSetting::firstOrCreate(['id' => 1]);

        $validated = $request->validate([
            'application_name' => 'required|string|max:100',
            'default_timezone' => 'required|string|max:50',
            'default_currency' => 'required|string|max:10',
            'default_date_format' => 'required|string|max:20',
            'decimal_precision' => 'required|integer|min:0|max:4',
            'quantity_precision' => 'required|integer|min:0|max:4',
            'default_language' => 'required|string|max:10',
            'email_notifications' => 'boolean',
            'attachment_size_limit_mb' => 'required|integer|min:1|max:50',
            'session_timeout_minutes' => 'required|integer|min:15|max:1440',
            'inventory_valuation_method' => 'required|in:Weighted Average,FIFO',
        ]);

        $validated['email_notifications'] = $request->boolean('email_notifications');

        $old = $system->toArray();
        $system->update($validated);
        Cache::flush();

        AuditService::log('settings', 'SYSTEM', 'update_system_settings', $old, $system->toArray(), 'Updated system parameters');

        return redirect()->back()->with('success', 'System settings saved successfully.');
    }
}
