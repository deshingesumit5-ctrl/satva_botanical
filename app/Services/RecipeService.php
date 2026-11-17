<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Models\RecipeStage;
use App\Models\RecipeStageActivity;
use App\Models\RecipeParameter;
use App\Models\RecipeMaterial;
use App\Models\RecipeEquipment;
use App\Models\RecipeQcTest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecipeService
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    /**
     * Validate whether a recipe version satisfies activation rules (Validations 4, 5, 6).
     */
    public function validateForActivation(RecipeVersion $version): array
    {
        $errors = [];

        // Validation 4: Recipe without stages
        if ($version->stages()->count() === 0) {
            $errors[] = 'Recipe must have at least one stage before activation.';
        }

        return $errors;
    }

    /**
     * Activate an approved recipe version.
     */
    public function activate(RecipeVersion $version, int $userId): void
    {
        $errors = $this->validateForActivation($version);
        if (!empty($errors)) {
            throw new RuntimeException(implode(' ', $errors));
        }

        DB::transaction(function () use ($version, $userId) {
            $recipe = $version->recipe;

            // Inactivate any previously active version for this recipe
            RecipeVersion::where('recipe_id', $recipe->id)
                ->where('status', 'Active')
                ->where('id', '!=', $version->id)
                ->update(['status' => 'Inactive']);

            $version->status = 'Active';
            $version->save();

            $recipe->current_version_id = $version->id;
            $recipe->status = 'Active';
            $recipe->save();

            AuditService::log('Recipe', 'Activate Recipe Version', "Recipe: {$recipe->recipe_code}, Version: {$version->version_number}", null, [
                'recipe_id' => $recipe->id,
                'version_id' => $version->id,
            ], 'Activated recipe version');
        });
    }

    /**
     * Create a new Draft version by deep-cloning an existing version.
     */
    public function createNewVersion(RecipeVersion $sourceVersion, string $newVersionNumber, int $userId): RecipeVersion
    {
        // Validation 3: Duplicate recipe version
        $exists = RecipeVersion::where('recipe_id', $sourceVersion->recipe_id)
            ->where('version_number', $newVersionNumber)
            ->exists();

        if ($exists) {
            throw new RuntimeException("Version '{$newVersionNumber}' already exists for this recipe.");
        }

        return DB::transaction(function () use ($sourceVersion, $newVersionNumber, $userId) {
            $newVersion = RecipeVersion::create([
                'recipe_id' => $sourceVersion->recipe_id,
                'version_number' => $newVersionNumber,
                'reference_batch_size' => $sourceVersion->reference_batch_size,
                'batch_uom_id' => $sourceVersion->batch_uom_id,
                'effective_from' => now()->toDateString(),
                'effective_to' => null,
                'description' => "Cloned from {$sourceVersion->version_number}",
                'status' => 'Draft',
            ]);

            foreach ($sourceVersion->stages as $sourceStage) {
                $newStage = RecipeStage::create([
                    'recipe_version_id' => $newVersion->id,
                    'stage_id' => $sourceStage->stage_id,
                    'sequence_no' => $sourceStage->sequence_no,
                    'occurrence_no' => $sourceStage->occurrence_no,
                    'is_mandatory' => $sourceStage->is_mandatory,
                    'is_optional' => $sourceStage->is_optional,
                    'is_conditional' => $sourceStage->is_conditional,
                    'condition_rule' => $sourceStage->condition_rule,
                    'is_repeatable' => $sourceStage->is_repeatable,
                    'repeat_config' => $sourceStage->repeat_config,
                    'min_repetitions' => $sourceStage->min_repetitions ?? 1,
                    'acceptance_parameter_ids' => $sourceStage->acceptance_parameter_ids,
                    'expected_process_conditions' => $sourceStage->expected_process_conditions,
                    'qc_required' => $sourceStage->qc_required,
                    'supervisor_auth_required' => $sourceStage->supervisor_auth_required ?? false,
                    'instructions' => $sourceStage->instructions,
                ]);

                // Clone activities
                $activityMap = [];
                foreach ($sourceStage->activities as $act) {
                    $newAct = RecipeStageActivity::create([
                        'recipe_stage_id' => $newStage->id,
                        'activity_id' => $act->activity_id,
                        'sequence_no' => $act->sequence_no,
                        'is_mandatory' => $act->is_mandatory,
                        'standard_duration_minutes' => $act->standard_duration_minutes,
                        'start_end_time_required' => $act->start_end_time_required,
                        'equipment_id' => $act->equipment_id,
                    ]);
                    $activityMap[$act->id] = $newAct->id;
                }

                // Clone parameters
                foreach ($sourceStage->parameters as $param) {
                    RecipeParameter::create([
                        'recipe_stage_id' => $newStage->id,
                        'recipe_stage_activity_id' => $param->recipe_stage_activity_id ? ($activityMap[$param->recipe_stage_activity_id] ?? null) : null,
                        'parameter_id' => $param->parameter_id,
                        'target_value' => $param->target_value,
                        'min_value' => $param->min_value,
                        'max_value' => $param->max_value,
                        'is_mandatory' => $param->is_mandatory,
                        'entry_frequency' => $param->entry_frequency,
                        'validation_type' => $param->validation_type,
                        'deviation_action' => $param->deviation_action,
                        'remarks' => $param->remarks,
                        'display_sequence' => $param->display_sequence,
                    ]);
                }

                // Clone materials
                foreach ($sourceStage->materials as $mat) {
                    RecipeMaterial::create([
                        'recipe_stage_id' => $newStage->id,
                        'direction' => $mat->direction,
                        'material_id' => $mat->material_id,
                        'product_id' => $mat->product_id,
                        'material_category' => $mat->material_category,
                        'standard_qty' => $mat->standard_qty,
                        'uom_id' => $mat->uom_id,
                        'quantity_basis' => $mat->quantity_basis,
                        'percentage_basis' => $mat->percentage_basis,
                        'is_mandatory' => $mat->is_mandatory,
                        'lot_tracking' => $mat->lot_tracking,
                        'multiple_lot_support' => $mat->multiple_lot_support,
                        'output_type' => $mat->output_type,
                        'next_stage_id' => $mat->next_stage_id,
                        'is_reusable' => $mat->is_reusable,
                        'is_saleable' => $mat->is_saleable,
                        'qc_required' => $mat->qc_required,
                        'destination_warehouse_id' => $mat->destination_warehouse_id,
                    ]);
                }

                // Clone equipments
                foreach ($sourceStage->equipments as $eq) {
                    RecipeEquipment::create([
                        'recipe_stage_id' => $newStage->id,
                        'equipment_id' => $eq->equipment_id,
                        'is_default' => $eq->is_default,
                        'remarks' => $eq->remarks,
                    ]);
                }

                // Clone QC tests
                foreach ($sourceStage->qcTests as $qc) {
                    RecipeQcTest::create([
                        'recipe_stage_id' => $newStage->id,
                        'qc_test_id' => $qc->qc_test_id,
                        'min_value' => $qc->min_value,
                        'max_value' => $qc->max_value,
                        'target_value' => $qc->target_value,
                        'is_mandatory' => $qc->is_mandatory,
                        'requires_approval' => $qc->requires_approval,
                        'blocks_next_stage' => $qc->blocks_next_stage,
                        'remarks' => $qc->remarks,
                    ]);
                }
            }

            AuditService::log('Recipe', 'Create New Version', "Recipe: {$sourceVersion->recipe->recipe_code}, Version: {$newVersionNumber}", null, [
                'from_version' => $sourceVersion->version_number,
                'new_version' => $newVersionNumber,
            ], 'Created new recipe draft version');

            return $newVersion;
        });
    }

    /**
     * Copy recipe to a completely new recipe.
     */
    public function copyRecipe(Recipe $sourceRecipe, string $newRecipeCode, string $newRecipeName): Recipe
    {
        return DB::transaction(function () use ($sourceRecipe, $newRecipeCode, $newRecipeName) {
            $newRecipe = Recipe::create([
                'recipe_code' => $newRecipeCode,
                'name' => $newRecipeName,
                'product_id' => $sourceRecipe->product_id,
                'status' => 'Draft',
            ]);

            $latestVersion = $sourceRecipe->currentVersion ?? $sourceRecipe->versions()->latest()->first();
            if ($latestVersion) {
                $this->createNewVersion($latestVersion, 'V1.0', Auth::id());
                // Link version to new recipe
                $newVersion = RecipeVersion::where('recipe_id', $latestVersion->recipe_id)
                    ->where('version_number', 'V1.0')
                    ->latest()
                    ->first();
                if ($newVersion) {
                    $newVersion->recipe_id = $newRecipe->id;
                    $newVersion->save();
                    $newRecipe->current_version_id = $newVersion->id;
                    $newRecipe->save();
                }
            }

            AuditService::log('Recipe', 'Copy Recipe', "New Recipe: {$newRecipeCode}", null, [
                'from' => $sourceRecipe->recipe_code,
                'to' => $newRecipeCode,
            ], 'Copied recipe');

            return $newRecipe;
        });
    }
}
