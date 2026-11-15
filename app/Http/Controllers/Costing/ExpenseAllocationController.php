<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\ExpenseAllocation;
use App\Models\MonthlyExpense;
use App\Services\CostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpenseAllocationController extends Controller
{
    protected CostingService $costingService;

    public function __construct(CostingService $costingService)
    {
        $this->costingService = $costingService;
    }

    public function index(Request $request)
    {
        $query = ExpenseAllocation::with(['items.batch.product', 'allocatedByUser']);

        if ($month = $request->input('month')) {
            $query->where('month', $month);
        }

        if ($year = $request->input('year')) {
            $query->where('year', $year);
        }

        $allocations = $query->latest()->paginate(10)->withQueryString();

        // Check unallocated approved expenses
        $unallocatedExpenses = MonthlyExpense::where('status', 'Approved')
            ->selectRaw('month, year, expense_category, sum(amount) as total_amount, count(*) as voucher_count')
            ->groupBy('month', 'year', 'expense_category')
            ->get();

        return view('costing.allocation.index', compact('allocations', 'unallocatedExpenses'));
    }

    public function save(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2035',
            'expense_category' => 'required|string',
        ]);

        try {
            $allocation = $this->costingService->allocateMonthlyExpenses(
                $validated['month'],
                $validated['year'],
                $validated['expense_category'],
                Auth::id()
            );

            return redirect()->route('costing.allocation.index')
                ->with('success', "Overhead allocation completed for {$validated['expense_category']} ({$validated['month']}/{$validated['year']}). Allocated to {$allocation->items()->count()} completed batches.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
