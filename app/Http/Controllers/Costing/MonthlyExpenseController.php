<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\MonthlyExpense;
use App\Models\Plant;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MonthlyExpenseController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = MonthlyExpense::with(['plant', 'enteredByUser']);

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
        $categories = MonthlyExpense::CATEGORIES;
        $years = MonthlyExpense::select('year')
            ->distinct()
            ->pluck('year')
            ->push((int) date('Y'))
            ->unique()
            ->sortDesc()
            ->values();

        return view('costing.expenses.index', compact('expenses', 'plants', 'categories', 'years'));
    }

    public function create()
    {
        $plants = Plant::where('status', 'Active')->get();
        $categories = MonthlyExpense::CATEGORIES;
        return view('costing.expenses.create', compact('plants', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plant_id' => 'nullable|exists:plants,id',
            'expense_category' => ['required', Rule::in(array_keys(MonthlyExpense::CATEGORIES))],
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2035',
            'amount' => 'required|numeric|min:0.01',
            'expense_number' => 'nullable|string|max:50|unique:monthly_expenses,expense_number',
            'voucher_number' => 'nullable|string|max:50|unique:monthly_expenses,expense_number',
            'entry_date' => 'nullable|date',
            'voucher_date' => 'nullable|date',
            'description' => 'nullable|string|max:1000',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $expense = DB::transaction(function () use ($validated) {
            $expenseNumber = $validated['expense_number']
                ?? $validated['voucher_number']
                ?? $this->docService->generate('EXPENSE');

            $entryDate = $validated['entry_date']
                ?? $validated['voucher_date']
                ?? now()->toDateString();

            $description = $validated['description']
                ?? $validated['remarks']
                ?? null;

            $expense = MonthlyExpense::create([
                'expense_number' => $expenseNumber,
                'plant_id' => $validated['plant_id'] ?? null,
                'expense_category' => $validated['expense_category'],
                'month' => (int) $validated['month'],
                'year' => (int) $validated['year'],
                'amount' => $validated['amount'],
                'description' => $description,
                'entry_date' => $entryDate,
                'entered_by' => Auth::id(),
                'status' => 'Draft',
            ]);

            AuditService::log(
                'Costing',
                'create_expense',
                $expense->expense_number,
                null,
                $expense->toArray(),
                'Recorded plant monthly expense'
            );

            return $expense;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense voucher {$expense->expense_number} created.",
                'redirect' => route('costing.expenses.index'),
            ]);
        }

        return redirect()->route('costing.expenses.index')
            ->with('success', "Expense voucher {$expense->expense_number} created.");
    }

    public function show(MonthlyExpense $expense)
    {
        $expense->load(['plant', 'enteredByUser', 'approvedByUser']);
        return view('costing.expenses.show', compact('expense'));
    }

    public function approve(Request $request, MonthlyExpense $expense)
    {
        if ($expense->status !== 'Draft') {
            $message = 'Only Draft expense vouchers can be approved.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $expense->status = 'Approved';
        $expense->approved_by = Auth::id();
        $expense->approved_at = now();
        $expense->save();

        AuditService::log('Costing', 'approve_expense', $expense->expense_number, ['status' => 'Draft'], ['status' => 'Approved'], 'Approved expense voucher');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense voucher {$expense->expense_number} approved.",
            ]);
        }

        return redirect()->back()->with('success', "Expense voucher {$expense->expense_number} approved.");
    }

    public function post(Request $request, MonthlyExpense $expense)
    {
        if ($expense->status !== 'Approved') {
            $message = 'Only Approved expense vouchers can be posted.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }
            return redirect()->back()->with('error', $message);
        }

        $expense->status = 'Posted';
        $expense->posted_at = now();
        $expense->save();

        AuditService::log('Costing', 'post_expense', $expense->expense_number, ['status' => 'Approved'], ['status' => 'Posted'], 'Posted expense voucher');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Expense voucher {$expense->expense_number} posted.",
            ]);
        }

        return redirect()->back()->with('success', "Expense voucher {$expense->expense_number} posted.");
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
