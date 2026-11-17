<?php

namespace App\Http\Controllers\Recipe;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\QcTest;
use App\Models\Recipe;
use App\Models\RecipeEquipment;
use App\Models\RecipeMaterial;
use App\Models\RecipeParameter;
use App\Models\RecipeQcTest;
use App\Models\RecipeStage;
use App\Models\RecipeStageActivity;
use App\Models\Stage;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\AuditService;
use App\Services\RecipeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecipeBuilderController extends Controller
{
    protected RecipeService $recipeService;

    public function __construct(RecipeService $recipeService)
    {
        $this->recipeService = $recipeService;
    }

    public function show(Recipe $recipe, int $step)
    {
        $version = $recipe->currentVersion;
        if (!$version) {
            return redirect()->route('recipe.recipes.index')->with('error', 'Recipe does not have an active draft version.');
        }

        // Lock check: if already approved/active, cannot edit builder
        $isReadOnly = in_array($version->status, ['Approved', 'Active']);

        switch ($step) {
            case 1:
                $products = Product::where('status', 'Active')->get();
                $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();
                return view('recipe.builder.step1', compact('recipe', 'version', 'step', 'products', 'uoms', 'isReadOnly'));

            case 2:
                $stages = Stage::where('status', 'Active')->orderBy('stage_name')->get();
                $parameters = Parameter::where('status', 'Active')->orderBy('name')->get();
                $recipeStages = RecipeStage::with(['stage', 'parameters.parameter'])
                    ->where('recipe_version_id', $version->id)
                    ->orderBy('sequence_no')
                    ->get();
                return view('recipe.builder.step2', compact('recipe', 'version', 'step', 'stages', 'parameters', 'recipeStages', 'isReadOnly'));

            case 3:
                $recipeStages = RecipeStage::with(['stage', 'activities.activity'])
                    ->where('recipe_version_id', $version->id)
                    ->orderBy('sequence_no')
                    ->get();
                $activities = Activity::where('status', 'Active')->orderBy('name')->get();
                $equipments = Equipment::where('status', 'Active')->get();
                return view('recipe.builder.step3', compact('recipe', 'version', 'step', 'recipeStages', 'activities', 'equipments', 'isReadOnly'));

            case 4:
                $recipeStages = RecipeStage::with(['stage', 'parameters.parameter'])
                    ->where('recipe_version_id', $version->id)
                    ->orderBy('sequence_no')
                    ->get();
                $parameters = Parameter::where('status', 'Active')->orderBy('name')->get();
                return view('recipe.builder.step4', compact('recipe', 'version', 'step', 'recipeStages', 'parameters', 'isReadOnly'));

            case 5:
                $recipeStages = RecipeStage::with(['stage', 'materials.material', 'materials.product', 'materials.uom'])
                    ->where('recipe_version_id', $version->id)
                    ->orderBy('sequence_no')
                    ->get();
                $materials = Material::where('status', 'Active')->orderBy('name')->get();
                $products = Product::where('status', 'Active')->orderBy('name')->get();
                $uoms = Uom::where('status', 'Active')->get();
                $warehouses = Warehouse::where('status', 'Active')->get();
                return view('recipe.builder.step5', compact('recipe', 'version', 'step', 'recipeStages', 'materials', 'products', 'uoms', 'warehouses', 'isReadOnly'));

            case 6:
                $recipeStages = RecipeStage::with(['stage', 'equipments.equipment'])
                    ->where('recipe_version_id', $version->id)
                    ->orderBy('sequence_no')
                    ->get();
                $equipments = Equipment::where('status', 'Active')->orderBy('name')->get();
                return view('recipe.builder.step6', compact('recipe', 'version', 'step', 'recipeStages', 'equipments', 'isReadOnly'));

            case 7:
                $recipeStages = RecipeStage::with(['stage', 'qcTests.qcTest', 'parameters.parameter'])
                    ->where('recipe_version_id', $version->id)
                    ->orderBy('sequence_no')
                    ->get();
                $qcTests = QcTest::where('status', 'Active')->orderBy('test_name')->get();
                $parameters = Parameter::where('status', 'Active')->orderBy('name')->get();
                return view('recipe.builder.step7', compact('recipe', 'version', 'step', 'recipeStages', 'qcTests', 'parameters', 'isReadOnly'));

            case 8:
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
                ]);
                $validationErrors = $this->recipeService->validateForActivation($version);
                return view('recipe.builder.step8', compact('recipe', 'version', 'step', 'validationErrors', 'isReadOnly'));

            default:
                return redirect()->route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => 1]);
        }
    }

    protected function normalizeProcessConditions(array $rawConditions): array
    {
        $clean = [];
        foreach ($rawConditions as $cond) {
            if (!is_array($cond)) {
                continue;
            }
            $paramId = !empty($cond['parameter_id']) ? (int) $cond['parameter_id'] : null;
            $paramName = $cond['parameter_name'] ?? null;
            $operator = $cond['operator'] ?? '=';
            $value = isset($cond['value']) && $cond['value'] !== '' ? (string) $cond['value'] : null;
            $maxValue = isset($cond['max_value']) && $cond['max_value'] !== '' ? (string) $cond['max_value'] : null;
            $holdTime = !empty($cond['hold_time_minutes']) ? (int) $cond['hold_time_minutes'] : null;

            if (($paramId || $paramName) && $value !== null) {
                if (!$paramName && $paramId) {
                    $paramName = Parameter::where('id', $paramId)->value('name') ?? "Parameter #{$paramId}";
                }
                $clean[] = [
                    'parameter_id'      => $paramId,
                    'parameter_name'    => $paramName,
                    'operator'          => in_array($operator, ['=', '>', '>=', '<', '<=', 'between'], true) ? $operator : '=',
                    'value'             => $value,
                    'max_value'         => $maxValue,
                    'hold_time_minutes' => $holdTime,
                ];
            }
        }
        return $clean;
    }

    public function saveStep(Request $request, Recipe $recipe, int $step)
    {
        $version = $recipe->currentVersion;
        if (!$version || in_array($version->status, ['Approved', 'Active'])) {
            return back()->with('error', 'Approved or Active recipe versions cannot be modified.');
        }

        switch ($step) {
            case 1:
                $validated = $request->validate([
                    'name' => 'required|string|max:150',
                    'reference_batch_size' => 'required|numeric|min:0.01',
                    'batch_uom_id' => 'required|exists:uoms,id',
                    'description' => 'nullable|string',
                ]);

                $recipe->name = $validated['name'];
                $recipe->save();

                $version->reference_batch_size = $validated['reference_batch_size'];
                $version->batch_uom_id = $validated['batch_uom_id'];
                $version->description = $validated['description'];
                $version->save();
                break;

            case 2:
                // Stage Flow list + Completion Requirements
                $stageIds = $request->input('stages', []);
                $instructions = $request->input('instructions', []);
                $qcRequired = $request->input('qc_required', []);
                $supervisorAuth = $request->input('supervisor_auth_required', []);
                $minRepetitions = $request->input('min_repetitions', []);
                $isOptional = $request->input('is_optional', []);
                $isConditional = $request->input('is_conditional', []);
                $conditionRules = $request->input('condition_rule', []);
                $acceptanceParams = $request->input('acceptance_parameter_ids', []);
                $processConditions = $request->input('expected_process_conditions', []);

                DB::transaction(function () use (
                    $version,
                    $stageIds,
                    $instructions,
                    $qcRequired,
                    $supervisorAuth,
                    $minRepetitions,
                    $isOptional,
                    $isConditional,
                    $conditionRules,
                    $acceptanceParams,
                    $processConditions
                ) {
                    $occurrences = [];
                    $sequence = 1;
                    $keptIds = [];

                    foreach ($stageIds as $idx => $stageId) {
                        $stageId = (int) $stageId;
                        $occurrences[$stageId] = ($occurrences[$stageId] ?? 0) + 1;
                        $occNo = $occurrences[$stageId];

                        $recipeStage = RecipeStage::firstOrNew([
                            'recipe_version_id' => $version->id,
                            'stage_id'          => $stageId,
                            'occurrence_no'     => $occNo,
                        ]);

                        $minReps = max(1, (int) ($minRepetitions[$idx] ?? $recipeStage->min_repetitions ?? 1));
                        $optFlag = !empty($isOptional[$idx]);
                        $condFlag = !empty($isConditional[$idx]);

                        $recipeStage->sequence_no = $sequence++;
                        $recipeStage->is_optional = $optFlag;
                        $recipeStage->is_conditional = $condFlag;
                        $recipeStage->is_mandatory = !$optFlag && !$condFlag;
                        $recipeStage->condition_rule = $conditionRules[$idx] ?? $recipeStage->condition_rule;
                        $recipeStage->is_repeatable = $minReps > 1 || $recipeStage->is_repeatable;
                        $recipeStage->min_repetitions = $minReps;
                        $recipeStage->instructions = $instructions[$idx] ?? null;
                        $recipeStage->qc_required = !empty($qcRequired[$idx]);
                        $recipeStage->supervisor_auth_required = !empty($supervisorAuth[$idx]);

                        if (array_key_exists($idx, $acceptanceParams)) {
                            $recipeStage->acceptance_parameter_ids = array_values(array_map('intval', array_filter((array) $acceptanceParams[$idx])));
                        }
                        if (array_key_exists($idx, $processConditions)) {
                            $recipeStage->expected_process_conditions = $this->normalizeProcessConditions((array) $processConditions[$idx]);
                        }

                        $recipeStage->save();
                        $keptIds[] = $recipeStage->id;
                    }

                    RecipeStage::where('recipe_version_id', $version->id)
                        ->whereNotIn('id', $keptIds)
                        ->delete();
                });
                break;

            case 3:
                // Activities Mapping
                $activitiesData = $request->input('activities', []);
                DB::transaction(function () use ($version, $activitiesData) {
                    foreach ($version->stages as $stage) {
                        $stageActs = $activitiesData[$stage->id] ?? [];
                        RecipeStageActivity::where('recipe_stage_id', $stage->id)->delete();

                        $seq = 1;
                        foreach ($stageActs as $act) {
                            if (!empty($act['activity_id'])) {
                                RecipeStageActivity::create([
                                    'recipe_stage_id' => $stage->id,
                                    'activity_id' => $act['activity_id'],
                                    'sequence_no' => $seq++,
                                    'is_mandatory' => isset($act['is_mandatory']),
                                    'standard_duration_minutes' => $act['duration'] ?? 60,
                                    'equipment_id' => !empty($act['equipment_id']) ? $act['equipment_id'] : null,
                                ]);
                            }
                        }
                    }
                });
                break;

            case 4:
                // Parameters Mapping
                $paramData = $request->input('parameters', []);
                DB::transaction(function () use ($version, $paramData) {
                    foreach ($version->stages as $stage) {
                        $stageParams = $paramData[$stage->id] ?? [];
                        RecipeParameter::where('recipe_stage_id', $stage->id)->delete();

                        $seq = 1;
                        foreach ($stageParams as $p) {
                            if (!empty($p['parameter_id'])) {
                                RecipeParameter::create([
                                    'recipe_stage_id' => $stage->id,
                                    'parameter_id' => $p['parameter_id'],
                                    'target_value' => $p['target_value'] ?? null,
                                    'min_value' => $p['min_value'] ?? null,
                                    'max_value' => $p['max_value'] ?? null,
                                    'is_mandatory' => isset($p['is_mandatory']),
                                    'deviation_action' => $p['deviation_action'] ?? 'Warning',
                                    'display_sequence' => $seq++,
                                ]);
                            }
                        }
                    }
                });
                break;

            case 5:
                // Materials Mapping
                $materialData = $request->input('materials', []);
                DB::transaction(function () use ($version, $materialData) {
                    foreach ($version->stages as $stage) {
                        $stageMats = $materialData[$stage->id] ?? [];
                        RecipeMaterial::where('recipe_stage_id', $stage->id)->delete();

                        foreach ($stageMats as $m) {
                            $direction = $m['direction'] ?? 'input';
                            $matId = !empty($m['material_id']) ? $m['material_id'] : null;
                            $prodId = !empty($m['product_id']) ? $m['product_id'] : null;
                            $stdQty = (float)($m['standard_qty'] ?? 0);
                            $outType = $m['output_type'] ?? ($direction === 'output' ? 'WIP' : null);

                            if ($matId || $prodId || !empty($outType) || $stdQty > 0) {
                                RecipeMaterial::create([
                                    'recipe_stage_id' => $stage->id,
                                    'direction' => $direction,
                                    'material_id' => $matId,
                                    'product_id' => $prodId,
                                    'material_category' => $m['material_category'] ?? ($direction === 'output' ? 'WIP' : 'Raw'),
                                    'standard_qty' => $stdQty,
                                    'uom_id' => $m['uom_id'] ?? $version->batch_uom_id,
                                    'output_type' => $outType,
                                    'is_mandatory' => true,
                                    'is_reusable' => isset($m['is_reusable']),
                                    'is_saleable' => isset($m['is_saleable']),
                                    'destination_warehouse_id' => !empty($m['warehouse_id']) ? $m['warehouse_id'] : null,
                                ]);
                            }
                        }
                    }
                });
                break;

            case 6:
                // Equipment Mapping
                $eqData = $request->input('equipments', []);
                DB::transaction(function () use ($version, $eqData) {
                    foreach ($version->stages as $stage) {
                        $stageEqs = $eqData[$stage->id] ?? [];
                        RecipeEquipment::where('recipe_stage_id', $stage->id)->delete();

                        foreach ($stageEqs as $eqId) {
                            if (!empty($eqId)) {
                                RecipeEquipment::create([
                                    'recipe_stage_id' => $stage->id,
                                    'equipment_id' => $eqId,
                                    'is_default' => true,
                                ]);
                            }
                        }
                    }
                });
                break;

            case 7:
                // QC Mapping & Stage Completion Requirements
                $qcData = $request->input('qc_tests', []);
                $completionReqs = $request->input('completion_requirements', []);

                DB::transaction(function () use ($version, $qcData, $completionReqs) {
                    foreach ($version->stages as $stage) {
                        $stageQcs = $qcData[$stage->id] ?? [];
                        RecipeQcTest::where('recipe_stage_id', $stage->id)->delete();

                        $hasBlockingQc = false;
                        foreach ($stageQcs as $q) {
                            if (!empty($q['qc_test_id'])) {
                                $blocksNext = isset($q['blocks_next_stage']);
                                if ($blocksNext) {
                                    $hasBlockingQc = true;
                                }
                                RecipeQcTest::create([
                                    'recipe_stage_id' => $stage->id,
                                    'qc_test_id' => $q['qc_test_id'],
                                    'target_value' => $q['target_value'] ?? null,
                                    'min_value' => $q['min_value'] ?? null,
                                    'max_value' => $q['max_value'] ?? null,
                                    'is_mandatory' => isset($q['is_mandatory']),
                                    'blocks_next_stage' => $blocksNext,
                                    'requires_approval' => true,
                                ]);
                            }
                        }

                        if (isset($completionReqs[$stage->id]) && is_array($completionReqs[$stage->id])) {
                            $req = $completionReqs[$stage->id];
                            $minReps = max(1, (int) ($req['min_repetitions'] ?? $stage->min_repetitions ?? 1));
                            $stage->min_repetitions = $minReps;
                            $stage->is_repeatable = $minReps > 1 || !empty($req['is_repeatable']);
                            $stage->qc_required = !empty($req['qc_required']) || $hasBlockingQc;
                            $stage->supervisor_auth_required = !empty($req['supervisor_auth_required']);
                            $stage->acceptance_parameter_ids = array_values(array_map('intval', array_filter((array) ($req['acceptance_parameter_ids'] ?? []))));
                            $stage->expected_process_conditions = $this->normalizeProcessConditions((array) ($req['expected_process_conditions'] ?? []));
                            $stage->save();
                        } elseif ($hasBlockingQc) {
                            $stage->qc_required = true;
                            $stage->save();
                        }
                    }
                });
                break;
        }

        AuditService::log('recipe', $recipe->recipe_code, "save_builder_step_{$step}", null, null, "Saved builder Step {$step}");

        $nextStep = $step < 8 ? $step + 1 : 8;
        return redirect()->route('recipe.builder.step', ['recipe' => $recipe->id, 'step' => $nextStep])
            ->with('success', "Step {$step} saved successfully.");
    }
}
