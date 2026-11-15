<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $company = CompanySetting::firstOrCreate(['id' => 1]);
        return view('settings.company', compact('company'));
    }

    public function update(Request $request)
    {
        $company = CompanySetting::firstOrCreate(['id' => 1]);

        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'address' => 'nullable|string',
            'contact_number' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'gstin' => 'nullable|string|max:15',
            'pan' => 'nullable|string|max:10',
            'website' => 'nullable|string|max:150',
            'default_currency' => 'required|string|max:10',
            'date_format' => 'required|string|max:20',
            'financial_year_start' => 'required|string|max:10',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('company', 'public');
        }

        $old = $company->toArray();
        $company->update($validated);
        AuditService::log('settings', 'COMPANY', 'update_company_settings', $old, $company->toArray(), 'Updated company profile');

        return redirect()->back()->with('success', 'Company settings updated successfully.');
    }
}
