<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditTrail;
use App\Models\User;
use Illuminate\Http\Request;

class AuditTrailController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditTrail::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('record_reference', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%");
            });
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($fromDate = $request->input('from_date')) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate = $request->input('to_date')) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $perPage = (int) $request->input('per_page', 25);
        $logs = $query->latest('id')->paginate($perPage)->withQueryString();

        $users = User::orderBy('name')->get();
        $modules = AuditTrail::select('module')->distinct()->pluck('module');
        $actions = AuditTrail::select('action')->distinct()->pluck('action');

        return view('admin.audit-trail.index', compact('logs', 'users', 'modules', 'actions'));
    }
}
