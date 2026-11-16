<?php

namespace App\Http\Controllers\Recipe;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Models\Uom;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use App\Services\RecipeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecipeController extends Controller
{
    protected RecipeService $recipeService;
    protected DocumentNumberService $docService;

    public function __construct(RecipeService $recipeService, DocumentNumberService $docService)
    {
        $this->recipeService = $recipeService;
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = Recipe::with(['product', 'currentVersion.batchUom', 'versions']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('recipe_code', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $recipes = $query->latest()->paginate(10)->withQueryString();

        return view('recipe.index', compact('recipes'));
    }

    public function create()
    {
        $products = Product::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();
        return view('recipe.create', compact('products', 'uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:150',
            'reference_batch_size' => 'required|numeric|min:0.01',
            'batch_uom_id' => 'required|exists:uoms,id',
            'description' => 'nullable|string',
        ]);

        $recipeCode = $this->docService->generate('RECIPE');

        $recipe = Recipe::create([
            'recipe_code' => $recipeCode,
            'name' => $validated['name'],
            'product_id' => $validated['product_id'],
            'status' => 'Draft',
            'remarks' => $validated['description'],
        ]);

        $version = RecipeVersion::create([
            'recipe_id' => $recipe->id,
            'version_number' => 'V1.0',
            'reference_batch_size' => $validated['reference_batch_size'],
            'batch_uom_id' => $validated['batch_uom_id'],
            'effective_from' => now(),
            'description' => $validated['description'] ?? 'Initial Version',
            'status' => 'Draft',
        ]);

        $recipe->current_version_id = $version->id;
        $recipe->save();

        AuditService::log('recipe', $recipe->recipe_code, 'create_recipe', null, $recipe->toArray(), 'Created recipe and version V1.0');

        return redirect()->route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 1])
            ->with('success', "Recipe '{$recipe->name}' created. Complete the 8-step builder to define process flow.");
    }

    public function show(Recipe $recipe)
    {
        $recipe->load([
            'product',
            'currentVersion.batchUom',
            'currentVersion.stages.stage',
            'currentVersion.stages.activities.activity',
            'currentVersion.stages.parameters.parameter',
            'currentVersion.stages.materials.material',
            'currentVersion.stages.materials.product',
            'currentVersion.stages.materials.uom',
            'currentVersion.stages.equipments.equipment',
            'currentVersion.stages.qcTests.qcTest',
            'versions.approvedByUser',
        ]);

        return view('recipe.show', compact('recipe'));
    }

    public function edit(Recipe $recipe)
    {
        $version = $recipe->currentVersion;
        if ($version && in_array($version->status, ['Approved', 'Active'])) {
            return redirect()->route('recipe.show', $recipe->id)
                ->with('warning', 'Approved or Active recipe versions are locked and cannot be edited directly. Please create a new version.');
        }

        return redirect()->route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 1]);
    }

    public function submit(Recipe $recipe)
    {
        $version = $recipe->currentVersion;
        if (!$version || $version->status !== 'Draft') {
            return back()->with('error', 'Only Draft recipe versions can be submitted for approval.');
        }

        $version->status = 'Pending Approval';
        $version->save();

        $recipe->status = 'Pending Approval';
        $recipe->save();

        AuditService::log('recipe', $recipe->recipe_code, 'submit_for_approval', null, ['version' => $version->version_number], 'Submitted recipe for approval');

        return redirect()->route('recipe.show', $recipe->id)->with('success', 'Recipe submitted for approval.');
    }

    public function approve(Request $request, Recipe $recipe)
    {
        $version = $recipe->currentVersion;
        if (!$version || $version->status !== 'Pending Approval') {
            return back()->with('error', 'Only recipes in Pending Approval status can be approved.');
        }

        $version->status = 'Approved';
        $version->approved_by = Auth::id();
        $version->approved_at = now();
        $version->approval_remarks = $request->input('remarks', 'Approved by QA/QC Lead');
        $version->save();

        $recipe->status = 'Approved';
        $recipe->approved_by = Auth::id();
        $recipe->approved_at = now();
        $recipe->save();

        AuditService::log('recipe', $recipe->recipe_code, 'approve_recipe', null, ['version' => $version->version_number], 'Recipe approved');

        return redirect()->route('recipe.show', $recipe->id)->with('success', 'Recipe version approved successfully.');
    }

    public function reject(Request $request, Recipe $recipe)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $version = $recipe->currentVersion;
        if (!$version || $version->status !== 'Pending Approval') {
            return back()->with('error', 'Only recipes in Pending Approval status can be rejected.');
        }

        $version->status = 'Rejected';
        $version->approval_remarks = $request->input('reason');
        $version->save();

        $recipe->status = 'Rejected';
        $recipe->remarks = $request->input('reason');
        $recipe->save();

        AuditService::log('recipe', $recipe->recipe_code, 'reject_recipe', null, ['reason' => $request->input('reason')], 'Recipe rejected');

        return redirect()->route('recipe.show', $recipe->id)->with('success', 'Recipe rejected and returned for revision.');
    }

    public function activate(Recipe $recipe)
    {
        $version = $recipe->currentVersion;
        if (!$version) {
            return back()->with('error', 'Recipe does not have a current version.');
        }

        try {
            $this->recipeService->activate($version, Auth::id());
            return redirect()->route('recipe.show', $recipe->id)->with('success', "Recipe Version {$version->version_number} is now Active and ready for batch manufacturing.");
        } catch (\Exception $e) {
            return back()->with('error', 'Activation failed: ' . $e->getMessage());
        }
    }

    public function deactivate(Recipe $recipe)
    {
        $version = $recipe->currentVersion;
        if ($version) {
            $version->status = 'Inactive';
            $version->save();
        }

        $recipe->status = 'Inactive';
        $recipe->save();

        AuditService::log('recipe', $recipe->recipe_code, 'deactivate_recipe', null, null, 'Deactivated recipe');

        return redirect()->route('recipe.show', $recipe->id)->with('success', 'Recipe deactivated.');
    }

    public function newVersion(Recipe $recipe)
    {
        $currentVersion = $recipe->currentVersion;
        if (!$currentVersion) {
            return back()->with('error', 'No existing version found to branch from.');
        }

        // Calculate next version number
        $parts = explode('.', str_replace('V', '', $currentVersion->version_number));
        $major = (int) ($parts[0] ?? 1) + 1;
        $newVersionNumber = "V{$major}.0";

        try {
            $newVer = $this->recipeService->createNewVersion($currentVersion, $newVersionNumber, Auth::id());
            $recipe->current_version_id = $newVer->id;
            $recipe->status = 'Draft';
            $recipe->save();

            return redirect()->route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 1])
                ->with('success', "New version {$newVersionNumber} created as Draft. Modify stages and submit for re-approval.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to create new version: ' . $e->getMessage());
        }
    }

    public function copy(Request $request, Recipe $recipe)
    {
        $request->validate(['name' => 'required|string|max:150']);

        try {
            $newRecipe = $this->recipeService->copyRecipe($recipe, $request->input('name'), Auth::id());
            return redirect()->route('recipe.show', $newRecipe->id)
                ->with('success', "Recipe duplicated successfully as '{$newRecipe->name}' ({$newRecipe->recipe_code}).");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to copy recipe: ' . $e->getMessage());
        }
    }

    public function versions(Recipe $recipe)
    {
        $recipe->load(['versions.batchUom', 'versions.approvedByUser']);
        return view('recipe.versions', compact('recipe'));
    }

    public function destroy(Recipe $recipe)
    {
        if ($recipe->batches()->exists() || $recipe->productionPlans()->exists()) {
            return back()->with('error', 'This record is already used and cannot be deleted. You can deactivate it.');
        }

        try {
            $recipe->delete();
            AuditService::log('recipe', $recipe->id, 'delete', null, null, 'Deleted recipe ' . ($recipe->name ?? $recipe->recipe_name));
            return redirect()->route('recipe.index')->with('success', 'Recipe deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'This record is already used and cannot be deleted. You can deactivate it.');
        }
    }

    public function toggleStatus(Recipe $recipe)
    {
        if ($recipe->status === 'Active') {
            return $this->deactivate($recipe);
        } else {
            return $this->activate($recipe);
        }
    }
}
