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

        if ($request->has('phone') && !$request->has('contact_number')) {
            $request->merge(['contact_number' => $request->input('phone')]);
        }

        $validated = $request->validate([
            'company_name' => 'required|string|max:150',
            'address' => 'nullable|string',
            'contact_number' => 'nullable|digits:10',
            'phone' => 'nullable|digits:10',
            'email' => 'nullable|email|max:100',
            'gstin' => 'nullable|string|max:15',
            'pan' => ['nullable', 'string', 'size:10', 'regex:/^[A-Za-z0-9]{10}$/'],
            'website' => 'nullable|string|max:150',
            'default_currency' => 'nullable|string|max:10',
            'date_format' => 'nullable|string|max:20',
            'financial_year_start' => 'nullable|string|max:10',
            'logo' => 'nullable|image|max:2048',
        ]);

        unset($validated['phone']);
        if (!empty($validated['pan'])) {
            $validated['pan'] = strtoupper($validated['pan']);
        }
        $validated = array_filter($validated, fn($v, $k) => !in_array($k, ['default_currency', 'date_format', 'financial_year_start']) || $v !== null, ARRAY_FILTER_USE_BOTH);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('company', 'public');
        }

        $old = $company->toArray();
        $company->update($validated);
        AuditService::log('settings', 'COMPANY', 'update_company_settings', $old, $company->toArray(), 'Updated company profile');

        return redirect()->back()->with('success', 'Company settings updated successfully.');
    }
}
