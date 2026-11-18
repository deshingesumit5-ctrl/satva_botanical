<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\ExpenseAllocation;
use App\Models\MonthlyExpense;
use App\Services\CostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ExpenseAllocationController extends Controller
{
    protected CostingService $costingService;

    public function __construct(CostingService $costingService)
    {
        $this->costingService = $costingService;
    }

    public function index(Request $request)
    {
        $query = ExpenseAllocation::with(['items.batch.product', 'postedByUser']);

        if ($month = $request->input('month')) {
            $query->where('month', $month);
        }

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }

        $allocations = $query->latest()->paginate(10)->withQueryString();

        $postedKeys = ExpenseAllocation::where('status', 'Posted')
            ->get(['month', 'year', 'expense_category'])
            ->map(fn($a) => "{$a->month}-{$a->year}-{$a->expense_category}")
            ->flip();

        // Check unallocated approved expenses
        $unallocatedExpenses = MonthlyExpense::where('status', 'Approved')
            ->selectRaw('month, year, expense_category, sum(amount) as total_amount, count(*) as voucher_count')
            ->groupBy('month', 'year', 'expense_category')
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(function ($ue) use ($postedKeys) {
                $ue->already_allocated = $postedKeys->has("{$ue->month}-{$ue->year}-{$ue->expense_category}");
                return $ue;
            });

        return view('costing.allocation.index', compact('allocations', 'unallocatedExpenses'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2035',
            'expense_category' => ['required', 'string', Rule::in(array_keys(MonthlyExpense::CATEGORIES))],
        ]);

        try {
            $allocation = $this->costingService->allocateMonthlyExpenses(
                (int) $validated['month'],
                (int) $validated['year'],
                $validated['expense_category'],
                Auth::id()
            );

            $message = "Overhead allocation completed for {$validated['expense_category']} ({$validated['month']}/{$validated['year']}). Allocated to {$allocation->items()->count()} completed batches.";

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'redirect' => route('costing.allocation.index'),
                ]);
            }

            return redirect()->route('costing.allocation.index')->with('success', $message);
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }
}
