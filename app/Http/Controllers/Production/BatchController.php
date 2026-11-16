<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\Plant;
use App\Models\Product;
use App\Models\ProductionPlan;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Models\Uom;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\ProductionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BatchController extends Controller
{
    protected ProductionService $productionService;
    protected DocumentNumberService $docService;

    public function __construct(ProductionService $productionService, DocumentNumberService $docService)
    {
        $this->productionService = $productionService;
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = Batch::with(['product', 'recipe', 'recipeVersion', 'productionPlan', 'stageExecutions']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($productId = $request->input('product_id')) {
            $query->where('product_id', $productId);
        }

        $batches = $query->latest()->paginate(10)->withQueryString();
        $products = Product::where('status', 'Active')->orderBy('name')->get();

        return view('production.batches.index', compact('batches', 'products'));
    }

    public function create(Request $request)
    {
        $plans = ProductionPlan::where('status', 'Approved')->with(['product', 'recipe.currentVersion'])->get();
        $products = Product::where('status', 'Active')->get();
        $recipes = Recipe::where('status', 'Active')->with('currentVersion.batchUom')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        $selectedPlan = null;
        if ($planId = $request->input('plan_id')) {
            $selectedPlan = ProductionPlan::with(['product', 'recipe.currentVersion'])->find($planId);
        }

        return view('production.batches.create', compact('plans', 'products', 'recipes', 'uoms', 'selectedPlan'));
    }

    public function store(Request $request)
    {
        if (!$request->has('planned_qty') && $request->has('planned_quantity')) {
            $request->merge(['planned_qty' => $request->input('planned_quantity')]);
        }
        if (!$request->has('planned_start_date') && $request->has('start_date')) {
            $request->merge(['planned_start_date' => $request->input('start_date')]);
        }
        if (!$request->has('planned_end_date') && $request->has('end_date')) {
            $request->merge(['planned_end_date' => $request->input('end_date')]);
        }

        $validated = $request->validate([
            'production_plan_id' => 'nullable|exists:production_plans,id',
            'product_id' => 'required|exists:products,id',
            'recipe_id' => 'required|exists:recipes,id',
            'planned_qty' => 'required|numeric|min:0.01',
            'uom_id' => 'required|exists:uoms,id',
            'planned_start_date' => 'required|date',
            'planned_end_date' => 'nullable|date|after_or_equal:planned_start_date',
            'supervisor_name' => 'nullable|string|max:100',
            'operator_count' => 'nullable|integer|min:1',
            'remarks' => 'nullable|string',
        ]);

        $recipe = Recipe::findOrFail($validated['recipe_id']);
        $version = $recipe->currentVersion;

        if (!$version || !in_array($version->status, ['Active', 'Approved'])) {
            return back()->withInput()->with('error', 'Cannot launch batch: The selected recipe has no Active approved version.');
        }

        $batchNumber = $this->docService->generate('BATCH');

        $batch = DB::transaction(function () use ($validated, $recipe, $version, $batchNumber) {
            $plantId = Plant::value('id');
            $newBatch = Batch::create([
                'batch_number' => $batchNumber,
                'production_plan_id' => $validated['production_plan_id'] ?? null,
                'product_id' => $validated['product_id'],
                'recipe_id' => $recipe->id,
                'recipe_version_id' => $version->id, // Freeze exact recipe version
                'planned_qty' => $validated['planned_qty'],
                'actual_qty' => 0,
                'uom_id' => $validated['uom_id'],
                'production_date' => $validated['planned_start_date'],
                'planned_start_date' => $validated['planned_start_date'],
                'planned_end_date' => $validated['planned_end_date'] ?? $validated['planned_start_date'],
                'plant_id' => $plantId,
                'supervisor_id' => Auth::id() ?? 1,
                'supervisor_name' => $validated['supervisor_name'] ?? (Auth::user() ? Auth::user()->name : 'Admin User'),
                'operator_count' => $validated['operator_count'] ?? 1,
                'status' => 'Draft',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Pre-calculate planned material requirements
            $this->productionService->generateMaterialRequirements($newBatch);

            // Start batch and initialize stage executions immediately
            $this->productionService->startBatch($newBatch, Auth::id() ?? 1);

            AuditService::log('production', $newBatch->batch_number, 'create_batch', null, $newBatch->toArray(), 'Created manufacturing batch');
            return $newBatch;
        });

        return redirect()->route('production.batches.show', $batch->id)
            ->with('success', "Batch {$batch->batch_number} created and started with all stages initialized.");
    }

    public function show(Batch $batch)
    {
        $batch->load([
            'product',
            'recipe',
            'recipeVersion',
            'productionPlan',
            'uom',
            'materialRequirements.material.baseUom',
            'materialRequirements.recipeStage.stage',
            'materialAllocations.stockLot',
            'materialConsumptions',
            'stageExecutions.activities',
            'stageExecutions.parameters',
            'stageExecutions.outputs',
            'stageExecutions.equipment',
            'holds.heldByUser',
            'reconciliation',
        ]);

        return view('production.batches.show', compact('batch'));
    }

    public function start(Batch $batch)
    {
        if (!in_array($batch->status, ['Created', 'Pending Start'])) {
            return back()->with('error', 'Batch cannot be started from its current status.');
        }

        try {
            $this->productionService->startBatch($batch, Auth::id());
            return redirect()->route('production.batches.execute', $batch->id)
                ->with('success', "Batch {$batch->batch_no} started! Stage 1 execution is now active.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to start batch: ' . $e->getMessage());
        }
    }

    public function hold(Request $request, Batch $batch)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->productionService->holdBatch($batch, $request->input('reason'), Auth::id());
            return redirect()->route('production.batches.show', $batch->id)
                ->with('warning', "Batch {$batch->batch_no} placed On Hold.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to hold batch: ' . $e->getMessage());
        }
    }

    public function resume(Request $request, Batch $batch)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->productionService->resumeBatch($batch, $request->input('reason'), Auth::id());
            return redirect()->route('production.batches.show', $batch->id)
                ->with('success', "Batch {$batch->batch_no} resumed and is back In Progress.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to resume batch: ' . $e->getMessage());
        }
    }

    public function close(Request $request, Batch $batch)
    {
        return redirect()->route('production.batches.reconcile', $batch->id);
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
