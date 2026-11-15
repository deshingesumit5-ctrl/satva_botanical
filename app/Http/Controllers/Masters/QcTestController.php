<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\QcTest;
use App\Services\AuditService;
use Illuminate\Http\Request;

class QcTestController extends Controller
{
    public function index(Request $request)
    {
        $query = QcTest::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('test_name', 'like', "%{$search}%")
                  ->orWhere('test_code', 'like', "%{$search}%")
                  ->orWhere('test_category', 'like', "%{$search}%")
                  ->orWhere('test_method', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('result_type')) {
            $query->where('result_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $qcTests = $query->latest()->paginate($perPage)->withQueryString();

        return view('masters.qc-tests.index', compact('qcTests'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'test_code' => 'required|string|max:30|unique:qc_tests,test_code',
            'test_name' => 'required|string|max:100',
            'test_category' => 'nullable|string|max:50',
            'test_method' => 'nullable|string|max:100',
            'result_type' => 'required|in:Numeric,Text,Pass-Fail,Dropdown',
            'uom' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $qcTest = QcTest::create($validated);
        AuditService::log('masters', $qcTest->test_code, 'create', null, $qcTest->toArray(), 'Created QC test');

        return redirect()->route('masters.qc-tests.index')->with('success', "QC Test '{$qcTest->test_name}' created successfully.");
    }

    public function edit(QcTest $qcTest)
    {
        if (request()->wantsJson()) {
            return response()->json($qcTest);
        }
        return view('masters.qc-tests.edit', compact('qcTest'));
    }

    public function update(Request $request, QcTest $qcTest)
    {
        $validated = $request->validate([
            'test_name' => 'required|string|max:100',
            'test_category' => 'nullable|string|max:50',
            'test_method' => 'nullable|string|max:100',
            'result_type' => 'required|in:Numeric,Text,Pass-Fail,Dropdown',
            'uom' => 'nullable|string|max:30',
            'description' => 'nullable|string',
            'status' => 'required|in:Active,Inactive',
        ]);

        $old = $qcTest->toArray();
        $qcTest->update($validated);
        AuditService::log('masters', $qcTest->test_code, 'update', $old, $qcTest->toArray(), 'Updated QC test');

        return redirect()->route('masters.qc-tests.index')->with('success', "QC Test '{$qcTest->test_name}' updated successfully.");
    }

    public function destroy(QcTest $qcTest)
    {
        if ($qcTest->recipeQcTests()->exists()) {
            return back()->with('error', "Cannot delete QC test '{$qcTest->test_name}' as it is mapped in recipe stages. You can deactivate it instead.");
        }

        $old = $qcTest->toArray();
        $qcTest->delete();
        AuditService::log('masters', $qcTest->test_code, 'delete', $old, null, 'Deleted QC test');

        return redirect()->route('masters.qc-tests.index')->with('success', "QC test deleted successfully.");
    }

    public function toggleStatus(QcTest $qcTest)
    {
        $old = $qcTest->status;
        $qcTest->status = ($qcTest->status === 'Active') ? 'Inactive' : 'Active';
        $qcTest->save();

        AuditService::log('masters', $qcTest->test_code, 'status_change', ['status' => $old], ['status' => $qcTest->status], 'Toggled QC test status');

        return redirect()->back()->with('success', "QC Test '{$qcTest->test_name}' status updated to {$qcTest->status}.");
    }

    public function create()
    {
        return redirect()->back();
    }

    public function show($id = null)
    {
        return redirect()->back();
    }
}
