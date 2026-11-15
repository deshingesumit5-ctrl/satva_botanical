<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Parameter;
use App\Models\Plant;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\QcTest;
use App\Models\Recipe;
use App\Models\RecipeEquipment;
use App\Models\RecipeMaterial;
use App\Models\RecipeParameter;
use App\Models\RecipeQcTest;
use App\Models\RecipeStage;
use App\Models\RecipeStageActivity;
use App\Models\RecipeVersion;
use App\Models\Stage;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DemoGarciniaSeeder extends Seeder
{
    public function run(): void
    {
        $kg = Uom::where('uom_code', 'KG')->first();
        $ltr = Uom::where('uom_code', 'LTR')->first();
        $degC = Uom::where('uom_code', 'DEG_C')->first();
        $hrs = Uom::where('uom_code', 'HRS')->first();
        $pct = Uom::where('uom_code', 'PCT')->first();
        $category = ProductCategory::first();
        $admin = User::first();
        $rmWh = Warehouse::where('code', 'WH-RM')->first();
        $fgWh = Warehouse::where('code', 'WH-FG')->first();
        $wipWh = Warehouse::where('code', 'WH-WIP')->first();

        // 1. Create Product
        $product = Product::updateOrCreate(
            ['product_code' => 'PRD-GARC-60'],
            [
                'name' => 'Garcinia Cambogia Extract 60% HCA',
                'product_category_id' => $category->id,
                'product_type' => 'Standard',
                'description' => 'Water soluble fine powder standardized to 60% Hydroxycitric Acid (HCA)',
                'hsn_code' => '13021919',
                'base_uom_id' => $kg->id,
                'batch_tracking' => true,
                'shelf_life' => 730, // 2 years
                'qc_required' => true,
                'is_saleable' => true,
                'status' => 'Active',
            ]
        );

        // 2. Create Materials
        $rawMaterial = Material::updateOrCreate(
            ['material_code' => 'RM-GARC-RIND'],
            [
                'name' => 'Garcinia Cambogia Dried Rinds',
                'material_category' => 'Raw',
                'material_type' => 'Solid',
                'grade' => 'Food Grade A',
                'hsn_code' => '12119099',
                'base_uom_id' => $kg->id,
                'lot_tracking' => true,
                'qc_required' => true,
                'status' => 'Active',
            ]
        );

        $solvent = Material::updateOrCreate(
            ['material_code' => 'CH-IPA-SOL'],
            [
                'name' => 'Isopropanol (IPA) Pure',
                'material_category' => 'Solvent',
                'material_type' => 'Liquid',
                'grade' => 'Industrial / Pharma Grade',
                'hsn_code' => '29051220',
                'base_uom_id' => $ltr->id,
                'lot_tracking' => true,
                'qc_required' => true,
                'status' => 'Active',
            ]
        );

        $deminWater = Material::updateOrCreate(
            ['material_code' => 'CH-DM-WTR'],
            [
                'name' => 'Demineralized Water',
                'material_category' => 'Water',
                'material_type' => 'Liquid',
                'grade' => 'Pure Process Water',
                'base_uom_id' => $ltr->id,
                'lot_tracking' => false,
                'qc_required' => false,
                'status' => 'Active',
            ]
        );

        $charcoal = Material::updateOrCreate(
            ['material_code' => 'CH-ACT-CHR'],
            [
                'name' => 'Activated Carbon Powder',
                'material_category' => 'Chemical',
                'material_type' => 'Powder',
                'grade' => 'Decolourizing Grade',
                'base_uom_id' => $kg->id,
                'lot_tracking' => true,
                'qc_required' => true,
                'status' => 'Active',
            ]
        );

        // 3. Create Equipments
        $extractor = Equipment::updateOrCreate(
            ['code' => 'EQ-EXT-01'],
            ['name' => 'Jacketed SS316 Extractor Vessel 5000L', 'category' => 'Extraction', 'capacity' => 5000, 'capacity_uom_id' => $ltr->id, 'status' => 'Active']
        );
        $filterPress = Equipment::updateOrCreate(
            ['code' => 'EQ-FLP-01'],
            ['name' => 'Sparkler Filter Press 24-inch', 'category' => 'Filtration', 'capacity' => 2000, 'capacity_uom_id' => $ltr->id, 'status' => 'Active']
        );
        $evaporator = Equipment::updateOrCreate(
            ['code' => 'EQ-FVE-01'],
            ['name' => 'Falling Film Vacuum Evaporator', 'category' => 'Evaporation', 'capacity' => 1500, 'capacity_uom_id' => $ltr->id, 'status' => 'Active']
        );
        $dryer = Equipment::updateOrCreate(
            ['code' => 'EQ-VTD-01'],
            ['name' => 'Vacuum Tray Dryer 48-Tray', 'category' => 'Drying', 'capacity' => 250, 'capacity_uom_id' => $kg->id, 'status' => 'Active']
        );
        $pulverizer = Equipment::updateOrCreate(
            ['code' => 'EQ-PLV-01'],
            ['name' => 'Pin Mill Pulverizer & Sifter', 'category' => 'Milling', 'capacity' => 100, 'capacity_uom_id' => $kg->id, 'status' => 'Active']
        );

        // 4. Create Parameters
        $paramTemp = Parameter::updateOrCreate(
            ['code' => 'PRM-TEMP'],
            ['name' => 'Process Temperature', 'parameter_type' => 'Numeric', 'uom' => '°C', 'decimal_precision' => 1, 'status' => 'Active']
        );
        $paramPress = Parameter::updateOrCreate(
            ['code' => 'PRM-VAC-PR'],
            ['name' => 'Vacuum Pressure', 'parameter_type' => 'Numeric', 'uom' => 'Bar', 'decimal_precision' => 2, 'status' => 'Active']
        );
        $paramPH = Parameter::updateOrCreate(
            ['code' => 'PRM-PH-VAL'],
            ['name' => 'pH Value', 'parameter_type' => 'Numeric', 'uom' => 'pH', 'decimal_precision' => 2, 'status' => 'Active']
        );
        $paramBrix = Parameter::updateOrCreate(
            ['code' => 'PRM-TDS-BRX'],
            ['name' => 'Total Dissolved Solids (Brix)', 'parameter_type' => 'Numeric', 'uom' => 'Brix', 'decimal_precision' => 1, 'status' => 'Active']
        );
        $paramMoist = Parameter::updateOrCreate(
            ['code' => 'PRM-MOIST-PCT'],
            ['name' => 'Moisture Content (%)', 'parameter_type' => 'Numeric', 'uom' => '%', 'decimal_precision' => 2, 'status' => 'Active']
        );

        // 5. Create QC Tests
        $qcAssay = QcTest::updateOrCreate(
            ['test_code' => 'QC-HCA-ASSAY'],
            ['test_name' => 'HCA Assay by HPLC', 'test_category' => 'Chemical', 'test_method' => 'HPLC In-house', 'result_type' => 'Numeric', 'uom' => '%', 'status' => 'Active']
        );
        $qcLoss = QcTest::updateOrCreate(
            ['test_code' => 'QC-LOD'],
            ['test_name' => 'Loss on Drying (LOD)', 'test_category' => 'Physical', 'test_method' => 'USP <731>', 'result_type' => 'Numeric', 'uom' => '%', 'status' => 'Active']
        );

        // 6. Create 12 Stages & Activities
        $stageDefs = [
            ['code' => 'STG-01', 'name' => 'Raw Material Cleaning & Grading', 'cat' => 'Processing', 'eq' => null, 'dur' => 120],
            ['code' => 'STG-02', 'name' => 'Coarse Grinding & Crushing', 'cat' => 'Processing', 'eq' => $pulverizer->id, 'dur' => 90],
            ['code' => 'STG-03', 'name' => 'Aqueous / Solvent Extraction', 'cat' => 'Processing', 'eq' => $extractor->id, 'dur' => 240],
            ['code' => 'STG-04', 'name' => 'Primary Filtration', 'cat' => 'Separation', 'eq' => $filterPress->id, 'dur' => 60],
            ['code' => 'STG-05', 'name' => 'Solvent Recovery & Concentration', 'cat' => 'Treatment', 'eq' => $evaporator->id, 'dur' => 180],
            ['code' => 'STG-06', 'name' => 'Decolourization & Carbon Treatment', 'cat' => 'Treatment', 'eq' => $extractor->id, 'dur' => 90],
            ['code' => 'STG-07', 'name' => 'Secondary Fine Polish Filtration', 'cat' => 'Separation', 'eq' => $filterPress->id, 'dur' => 60],
            ['code' => 'STG-08', 'name' => 'Precipitation & Calcium Chelation', 'cat' => 'Treatment', 'eq' => $extractor->id, 'dur' => 150],
            ['code' => 'STG-09', 'name' => 'Centrifugation & Wet Cake Washing', 'cat' => 'Separation', 'eq' => $filterPress->id, 'dur' => 120],
            ['code' => 'STG-10', 'name' => 'Vacuum Tray Drying', 'cat' => 'Drying', 'eq' => $dryer->id, 'dur' => 480],
            ['code' => 'STG-11', 'name' => 'Micro-Pulverization & Sifting', 'cat' => 'Processing', 'eq' => $pulverizer->id, 'dur' => 120],
            ['code' => 'STG-12', 'name' => 'Final QC Inspection & Packaging', 'cat' => 'Processing', 'eq' => null, 'dur' => 60],
        ];

        $createdStages = [];
        foreach ($stageDefs as $s) {
            $createdStage = Stage::updateOrCreate(
                ['stage_code' => $s['code']],
                [
                    'stage_name' => $s['name'],
                    'category' => $s['cat'],
                    'default_equipment_id' => $s['eq'],
                    'default_duration' => $s['dur'],
                    'duration_uom' => 'Minutes',
                    'activity_required' => true,
                    'material_input_required' => true,
                    'material_output_required' => true,
                    'qc_available' => true,
                    'status' => 'Active',
                ]
            );
            $createdStages[$s['code']] = $createdStage;

            // Create activity for stage
            Activity::updateOrCreate(
                ['code' => 'ACT-' . $s['code']],
                [
                    'name' => 'Standard Operation for ' . $s['name'],
                    'activity_type' => 'Process',
                    'default_duration' => $s['dur'],
                    'duration_uom' => 'Minutes',
                    'start_time_required' => true,
                    'end_time_required' => true,
                    'status' => 'Active',
                ]
            );
        }

        // 7. Create Recipe & Version 1.0 (Active)
        $recipe = Recipe::updateOrCreate(
            ['recipe_code' => 'RCP-GARC-001'],
            [
                'name' => 'Garcinia Cambogia Extract 60% Standard SOP',
                'product_id' => $product->id,
                'status' => 'Active',
                'approved_by' => $admin ? $admin->id : null,
                'approved_at' => now(),
                'remarks' => 'Standard validated manufacturing formula for commercial Garcinia extraction',
            ]
        );

        $version = RecipeVersion::updateOrCreate(
            ['recipe_id' => $recipe->id, 'version_number' => 'V1.0'],
            [
                'reference_batch_size' => 1000.0000, // 1000 kg raw material batch
                'batch_uom_id' => $kg->id,
                'effective_from' => now()->startOfYear(),
                'effective_to' => null,
                'description' => 'Initial approved master formula',
                'status' => 'Active',
                'approved_by' => $admin ? $admin->id : null,
                'approved_at' => now(),
                'approval_remarks' => 'Approved by Plant Quality Unit',
            ]
        );

        $recipe->current_version_id = $version->id;
        $recipe->save();

        // 8. Link stages to RecipeVersion
        $seq = 1;
        foreach ($stageDefs as $s) {
            $stageModel = $createdStages[$s['code']];
            $recipeStage = RecipeStage::updateOrCreate(
                ['recipe_version_id' => $version->id, 'stage_id' => $stageModel->id, 'occurrence_no' => 1],
                [
                    'sequence_no' => $seq++,
                    'is_mandatory' => true,
                    'qc_required' => in_array($s['code'], ['STG-03', 'STG-05', 'STG-10', 'STG-12']),
                    'instructions' => 'Follow standard Operating Procedure for ' . $s['name'],
                ]
            );

            // Link activity
            $act = Activity::where('code', 'ACT-' . $s['code'])->first();
            if ($act) {
                RecipeStageActivity::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'activity_id' => $act->id],
                    ['sequence_no' => 1, 'is_mandatory' => true, 'standard_duration_minutes' => $s['dur']]
                );
            }

            // Stage-specific mappings
            if ($s['code'] === 'STG-01') {
                // Input raw material
                RecipeMaterial::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'material_id' => $rawMaterial->id, 'direction' => 'input'],
                    ['standard_qty' => 1000.0, 'uom_id' => $kg->id, 'is_mandatory' => true]
                );
            }

            if ($s['code'] === 'STG-03') {
                // Input solvent
                RecipeMaterial::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'material_id' => $solvent->id, 'direction' => 'input'],
                    ['standard_qty' => 3000.0, 'uom_id' => $ltr->id, 'is_mandatory' => true]
                );
                // Parameter: temp
                RecipeParameter::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'parameter_id' => $paramTemp->id],
                    ['target_value' => '65', 'min_value' => '60', 'max_value' => '70', 'is_mandatory' => true]
                );
            }

            if ($s['code'] === 'STG-05') {
                // Output: Recovered solvent
                RecipeMaterial::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'direction' => 'output', 'material_category' => 'Solvent'],
                    ['standard_qty' => 2400.0, 'uom_id' => $ltr->id, 'output_type' => 'Recovered', 'is_reusable' => true]
                );
                // Output: WIP concentrated liquid
                RecipeMaterial::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'direction' => 'output', 'material_category' => 'WIP'],
                    ['standard_qty' => 500.0, 'uom_id' => $ltr->id, 'output_type' => 'WIP']
                );
                // Parameter: Brix
                RecipeParameter::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'parameter_id' => $paramBrix->id],
                    ['target_value' => '35', 'min_value' => '30', 'max_value' => '40', 'is_mandatory' => true]
                );
            }

            if ($s['code'] === 'STG-10') {
                // Parameter: moisture
                RecipeParameter::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'parameter_id' => $paramMoist->id],
                    ['target_value' => '4.0', 'min_value' => '2.0', 'max_value' => '5.0', 'is_mandatory' => true]
                );
            }

            if ($s['code'] === 'STG-12') {
                // Final product output
                RecipeMaterial::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'product_id' => $product->id, 'direction' => 'output'],
                    ['standard_qty' => 150.0, 'uom_id' => $kg->id, 'output_type' => 'Finished', 'is_saleable' => true]
                );
                // Final QC test
                RecipeQcTest::updateOrCreate(
                    ['recipe_stage_id' => $recipeStage->id, 'qc_test_id' => $qcAssay->id],
                    ['target_value' => '60.0', 'min_value' => '60.0', 'max_value' => '65.0', 'is_mandatory' => true, 'requires_approval' => true, 'blocks_next_stage' => true]
                );
            }
        }
    }
}
