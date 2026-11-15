<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\MonthlyExpense;
use App\Models\Plant;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MonthlyExpenseController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = MonthlyExpense::with(['plant', 'createdByUser']);

        if ($month = $request->input('month')) {
            $query->where('month', $month);
        }

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }

        if ($category = $request->input('category')) {
            $query->where('expense_category', $category);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $expenses = $query->latest()->paginate(15)->withQueryString();
        $plants = Plant::where('status', 'Active')->get();

        return view('costing.expenses.index', compact('expenses', 'plants'));
    }

    public function create()
    {
        $plants = Plant::where('status', 'Active')->get();
        return view('costing.expenses.create', compact('plants'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'expense_category' => 'required|in:Labour,Power,Fuel,Factory Overhead,Admin Overhead,Other',
            'expense_name' => 'required|string|max:150',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2035',
            'amount' => 'required|numeric|min:0.01',
            'voucher_number' => 'nullable|string|max:50',
            'voucher_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        if (empty($validated['voucher_number'])) {
            $validated['voucher_number'] = $this->docService->generate('EXPENSE');
        }

        $expense = MonthlyExpense::create(array_merge($validated, [
            'status' => 'Draft',
            'created_by' => Auth::id(),
        ]));

        AuditService::log('costing', $expense->voucher_number, 'create_expense', null, $expense->toArray(), 'Recorded plant monthly expense');

        return redirect()->route('costing.expenses.index')->with('success', "Expense voucher {$expense->voucher_number} created.");
    }

    public function show(MonthlyExpense $expense)
    {
        $expense->load(['plant', 'createdByUser', 'approvedByUser']);
        return view('costing.expenses.show', compact('expense'));
    }

    public function approve(MonthlyExpense $expense)
    {
        $expense->status = 'Approved';
        $expense->approved_by = Auth::id();
        $expense->approved_at = now();
        $expense->save();

        AuditService::log('costing', $expense->voucher_number, 'approve_expense', null, null, 'Approved expense voucher');

        return redirect()->back()->with('success', "Expense voucher {$expense->voucher_number} approved.");
    }

    public function post(MonthlyExpense $expense)
    {
        $expense->status = 'Posted';
        $expense->save();

        AuditService::log('costing', $expense->voucher_number, 'post_expense', null, null, 'Posted expense voucher');

        return redirect()->back()->with('success', "Expense voucher {$expense->voucher_number} posted.");
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
