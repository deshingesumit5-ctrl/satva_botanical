<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\Recipe;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductionPlanController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = ProductionPlan::with(['product', 'recipe', 'plant', 'warehouse', 'batches']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('plan_no', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $plans = $query->latest()->paginate(10)->withQueryString();

        return view('production.plans.index', compact('plans'));
    }

    public function create()
    {
        $products = Product::where('status', 'Active')->with(['recipes' => function ($q) {
            $q->where('status', 'Active');
        }])->get();
        $recipes = Recipe::where('status', 'Active')->with('currentVersion.batchUom')->get();
        $plants = Plant::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->where('type', 'Finished Goods')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('production.plans.create', compact('products', 'recipes', 'plants', 'warehouses', 'uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'recipe_id' => 'required|exists:recipes,id',
            'plant_id' => 'required|exists:plants,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'planned_qty' => 'required|numeric|min:0.01',
            'uom_id' => 'required|exists:uoms,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remarks' => 'nullable|string',
        ]);

        $planNo = $this->docService->generate('PROD_PLAN');

        $plan = ProductionPlan::create([
            'plan_no' => $planNo,
            'product_id' => $validated['product_id'],
            'recipe_id' => $validated['recipe_id'],
            'plant_id' => $validated['plant_id'],
            'warehouse_id' => $validated['warehouse_id'],
            'planned_qty' => $validated['planned_qty'],
            'uom_id' => $validated['uom_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'Draft',
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);

        AuditService::log('production', $plan->plan_no, 'create_plan', null, $plan->toArray(), 'Created production plan');

        return redirect()->route('production.plans.show', $plan->id)->with('success', "Production Plan {$plan->plan_no} created.");
    }

    public function show(ProductionPlan $plan)
    {
        $plan->load(['product', 'recipe.currentVersion.stages.inputMaterials.material', 'plant', 'warehouse', 'batches.product', 'uom']);
        return view('production.plans.show', compact('plan'));
    }

    public function edit(ProductionPlan $plan)
    {
        if ($plan->status !== 'Draft') {
            return redirect()->route('production.plans.show', $plan->id)->with('warning', 'Only Draft plans can be edited.');
        }

        $products = Product::where('status', 'Active')->get();
        $recipes = Recipe::where('status', 'Active')->get();
        $plants = Plant::where('status', 'Active')->get();
        $warehouses = Warehouse::where('status', 'Active')->where('type', 'Finished Goods')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('production.plans.edit', compact('plan', 'products', 'recipes', 'plants', 'warehouses', 'uoms'));
    }

    public function update(Request $request, ProductionPlan $plan)
    {
        if ($plan->status !== 'Draft') {
            return back()->with('error', 'Only Draft plans can be updated.');
        }

        $validated = $request->validate([
            'planned_qty' => 'required|numeric|min:0.01',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'remarks' => 'nullable|string',
        ]);

        $plan->update($validated);
        AuditService::log('production', $plan->plan_no, 'update_plan', null, $plan->toArray(), 'Updated production plan');

        return redirect()->route('production.plans.show', $plan->id)->with('success', 'Production Plan updated.');
    }

    public function destroy(ProductionPlan $plan)
    {
        if ($plan->status !== 'Draft') {
            return back()->with('error', 'Only Draft plans can be deleted.');
        }

        $code = $plan->plan_no;
        $plan->delete();
        AuditService::log('production', $code, 'delete_plan', null, null, 'Deleted production plan');

        return redirect()->route('production.plans.index')->with('success', 'Production Plan deleted.');
    }

    public function approve(ProductionPlan $plan)
    {
        if ($plan->status !== 'Draft') {
            return back()->with('error', 'Only Draft plans can be approved.');
        }

        $plan->status = 'Approved';
        $plan->approved_by = Auth::id();
        $plan->approved_at = now();
        $plan->save();

        AuditService::log('production', $plan->plan_no, 'approve_plan', null, null, 'Approved production plan');

        return redirect()->route('production.plans.show', $plan->id)->with('success', "Production Plan {$plan->plan_no} approved. Batches can now be launched.");
    }

    public function reject(Request $request, ProductionPlan $plan)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $plan->status = 'Rejected';
        $plan->remarks = ($plan->remarks ? $plan->remarks . "\n" : '') . "Rejection reason: " . $request->input('reason');
        $plan->save();

        AuditService::log('production', $plan->plan_no, 'reject_plan', null, ['reason' => $request->input('reason')], 'Rejected production plan');

        return redirect()->route('production.plans.show', $plan->id)->with('success', 'Production Plan rejected.');
    }
}
