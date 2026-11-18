<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Batch;
use App\Models\BatchActivityExecution;
use App\Models\BatchMaterialConsumption;
use App\Models\BatchParameterReading;
use App\Models\BatchStageExecution;
use App\Models\BatchStageOutput;
use App\Models\Material;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\QcRequest;
use App\Models\Recipe;
use App\Models\RecipeMaterial;
use App\Models\RecipeParameter;
use App\Models\RecipeStage;
use App\Models\RecipeStageActivity;
use App\Models\RecipeVersion;
use App\Models\ReworkRecord;
use App\Models\Stage;
use App\Models\StockLot;
use App\Models\Uom;
use App\Models\User;
use App\Services\StageRequirementEvaluator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StageRequirementEvaluatorTest extends TestCase
{
    use DatabaseTransactions;

    protected StageRequirementEvaluator $evaluator;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = app(StageRequirementEvaluator::class);
        $this->admin = User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    protected function createBaseStageExecution(array $stageOverrides = []): array
    {
        $product = Product::firstOrFail();
        $uom = Uom::firstOrFail();
        $stage = Stage::firstOrFail();

        $recipe = Recipe::create([
            'recipe_code' => 'RCP-UNIT-' . uniqid(),
            'name'        => 'Unit Eval Recipe',
            'product_id'  => $product->id,
            'status'      => 'Active',
        ]);

        $version = RecipeVersion::create([
            'recipe_id'            => $recipe->id,
            'version_number'       => 'V1.0',
            'reference_batch_size' => 100,
            'batch_uom_id'         => $uom->id,
            'effective_from'       => now()->toDateString(),
            'status'               => 'Active',
        ]);

        $recipeStage = RecipeStage::create(array_merge([
            'recipe_version_id' => $version->id,
            'stage_id'          => $stage->id,
            'sequence_no'       => 1,
            'occurrence_no'     => 1,
            'is_mandatory'      => true,
            'min_repetitions'   => 1,
        ], $stageOverrides));

        $batch = Batch::create([
            'batch_number'       => 'BAT-UNIT-' . uniqid(),
            'product_id'         => $product->id,
            'recipe_id'          => $recipe->id,
            'recipe_version_id'  => $version->id,
            'planned_qty'        => 100,
            'uom_id'             => $uom->id,
            'production_date'    => now()->toDateString(),
            'planned_start_date' => now()->toDateString(),
            'planned_end_date'   => now()->addDay()->toDateString(),
            'supervisor_id'      => $this->admin->id,
            'supervisor_name'    => $this->admin->name,
            'operator_count'     => 1,
            'status'             => 'In Progress',
        ]);

        $execution = BatchStageExecution::create([
            'batch_id'         => $batch->id,
            'recipe_stage_id'  => $recipeStage->id,
            'execution_number' => 1,
            'stage_name'       => 'Unit Stage',
            'sequence_no'      => 1,
            'occurrence_no'    => 1,
            'execution_type'   => 'Original',
            'status'           => 'In Progress',
            'qc_status'        => !empty($stageOverrides['qc_required']) ? 'Pending' : 'Not Required',
            'start_time'       => now(),
        ]);

        return compact('recipe', 'version', 'recipeStage', 'batch', 'execution', 'uom');
    }

    public function test_required_activity_missing_end_time_explains_why_and_what_to_do(): void
    {
        $ctx = $this->createBaseStageExecution();
        $act = Activity::firstOrCreate(
            ['code' => 'ACT-CLEAN-UNIT'],
            ['name' => 'Cleaning', 'status' => 'Active']
        );
        $rsa = RecipeStageActivity::create([
            'recipe_stage_id' => $ctx['recipeStage']->id,
            'activity_id'     => $act->id,
            'sequence_no'     => 1,
            'is_mandatory'    => true,
        ]);
        BatchActivityExecution::create([
            'batch_stage_execution_id' => $ctx['execution']->id,
            'recipe_stage_activity_id' => $rsa->id,
            'activity_name'            => 'Cleaning',
            'sequence_no'              => 1,
            'start_datetime'           => now(),
            'end_datetime'             => null,
            'status'                   => 'In Progress',
        ]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'activities');

        $this->assertFalse($item['met']);
        $this->assertStringContainsString("Activity 'Cleaning' has no end time.", $item['reason']);
        $this->assertStringContainsString("Enter the start and end date-time for 'Cleaning' in Section B.", $item['action']);
        $this->assertEquals('#section-b', $item['anchor']);
    }

    public function test_mandatory_parameter_reading_missing_explains_why_and_what_to_do(): void
    {
        $ctx = $this->createBaseStageExecution();
        $param = Parameter::firstOrCreate(
            ['code' => 'PARAM-PH-UNIT'],
            ['name' => 'pH', 'parameter_type' => 'Numeric', 'uom' => 'pH', 'status' => 'Active']
        );
        $rp = RecipeParameter::create([
            'recipe_stage_id'  => $ctx['recipeStage']->id,
            'parameter_id'     => $param->id,
            'target_value'     => '1.9',
            'min_value'        => '1.8',
            'max_value'        => '2.0',
            'is_mandatory'     => true,
            'deviation_action' => 'Warning',
        ]);
        BatchParameterReading::create([
            'batch_stage_execution_id' => $ctx['execution']->id,
            'recipe_parameter_id'      => $rp->id,
            'parameter_name'           => 'pH',
            'min_value'                => '1.8',
            'max_value'                => '2.0',
            'actual_value'             => null,
        ]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'mandatory_readings');

        $this->assertFalse($item['met']);
        $this->assertStringContainsString("Reading for 'pH' is missing.", $item['reason']);
        $this->assertStringContainsString('Enter the actual value in Section C.', $item['action']);
        $this->assertEquals('#section-c', $item['anchor']);
    }

    public function test_parameter_acceptance_out_of_range_explains_why_and_what_to_do(): void
    {
        $param = Parameter::firstOrCreate(
            ['code' => 'PARAM-PH-UNIT2'],
            ['name' => 'pH', 'parameter_type' => 'Numeric', 'uom' => 'pH', 'status' => 'Active']
        );
        $ctx = $this->createBaseStageExecution([
            'acceptance_parameter_ids' => [$param->id],
        ]);
        $rp = RecipeParameter::create([
            'recipe_stage_id'  => $ctx['recipeStage']->id,
            'parameter_id'     => $param->id,
            'target_value'     => '1.9',
            'min_value'        => '1.8',
            'max_value'        => '2.0',
            'is_mandatory'     => true,
            'deviation_action' => 'Block',
        ]);
        $reading = BatchParameterReading::create([
            'batch_stage_execution_id' => $ctx['execution']->id,
            'recipe_parameter_id'      => $rp->id,
            'parameter_name'           => 'pH',
            'min_value'                => '1.8',
            'max_value'                => '2.0',
            'actual_value'             => '2.2',
            'is_within_range'          => false,
        ]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'param_accept_' . $param->id);

        $this->assertFalse($item['met']);
        $this->assertEquals('pH entered 2.2, accepted range 1.8-2.0.', $item['reason']);
        $this->assertStringContainsString('Check the reading and enter a corrected new reading within range', $item['action']);
        $this->assertEquals('2.2', $reading->fresh()->actual_value);
    }

    public function test_expected_process_condition_explains_why_and_what_to_do(): void
    {
        $temp = Parameter::firstOrCreate(
            ['code' => 'PARAM-TEMP-UNIT'],
            ['name' => 'Temperature', 'parameter_type' => 'Numeric', 'uom' => 'C', 'status' => 'Active']
        );
        $ctx = $this->createBaseStageExecution([
            'expected_process_conditions' => [[
                'parameter_id'      => $temp->id,
                'parameter_name'    => 'Temperature',
                'operator'          => '>=',
                'value'             => '60',
                'hold_time_minutes' => 30,
            ]],
        ]);
        $rp = RecipeParameter::create([
            'recipe_stage_id' => $ctx['recipeStage']->id,
            'parameter_id'    => $temp->id,
            'target_value'    => '65',
            'min_value'       => '60',
            'max_value'       => '75',
            'is_mandatory'    => false,
        ]);
        BatchParameterReading::create([
            'batch_stage_execution_id' => $ctx['execution']->id,
            'recipe_parameter_id'      => $rp->id,
            'parameter_name'           => 'Temperature',
            'actual_value'             => '55',
        ]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'process_cond_0');

        $this->assertFalse($item['met']);
        $this->assertStringContainsString('Temperature must be >= 60 for 30 minutes; last value 55', $item['reason']);
        $this->assertStringContainsString('Record a Temperature reading >= 60 in Section C', $item['action']);
    }

    public function test_minimum_repetitions_explains_why_and_what_to_do(): void
    {
        $ctx = $this->createBaseStageExecution([
            'is_repeatable'   => true,
            'min_repetitions' => 3,
        ]);
        BatchStageExecution::create([
            'batch_id'         => $ctx['batch']->id,
            'recipe_stage_id'  => $ctx['recipeStage']->id,
            'execution_number' => 2,
            'stage_name'       => 'Unit Stage (Repetition #2)',
            'sequence_no'      => 1,
            'occurrence_no'    => 1,
            'execution_type'   => 'Repeat',
            'status'           => 'In Progress',
        ]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'repetitions');

        $this->assertFalse($item['met']);
        $this->assertEquals('2 of 3 repetitions completed.', $item['reason']);
        $this->assertEquals('Add 1 more repetition using Add Repetition.', $item['action']);
    }

    public function test_material_consumption_missing_explains_why_and_what_to_do(): void
    {
        $ctx = $this->createBaseStageExecution();
        $material = Material::firstOrFail();
        RecipeMaterial::create([
            'recipe_stage_id' => $ctx['recipeStage']->id,
            'material_id'     => $material->id,
            'direction'       => 'input',
            'standard_qty'    => 10,
            'uom_id'          => $ctx['uom']->id,
            'is_mandatory'    => true,
        ]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'material_consumption');

        $this->assertFalse($item['met']);
        $this->assertStringContainsString($material->name, $item['reason']);
        $this->assertStringContainsString('Enter the actual charged quantity in Section D.', $item['action']);
        $this->assertEquals('#section-d', $item['anchor']);
    }

    public function test_stage_outputs_missing_explains_why_and_what_to_do(): void
    {
        $ctx = $this->createBaseStageExecution();
        $material = Material::firstOrFail();
        RecipeMaterial::create([
            'recipe_stage_id' => $ctx['recipeStage']->id,
            'material_id'     => $material->id,
            'direction'       => 'output',
            'output_type'     => 'WIP',
            'standard_qty'    => 20,
            'uom_id'          => $ctx['uom']->id,
            'is_mandatory'    => true,
        ]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'stage_outputs');

        $this->assertFalse($item['met']);
        $this->assertEquals('Output quantity is missing or zero.', $item['reason']);
        $this->assertEquals('Enter the output quantity in Section E.', $item['action']);
        $this->assertEquals('#section-e', $item['anchor']);
    }

    public function test_qc_pending_rejected_and_rework_explain_why_and_what_to_do(): void
    {
        $ctx = $this->createBaseStageExecution(['qc_required' => true]);
        $exec = $ctx['execution'];
        $exec->status = 'QC Pending';
        $exec->qc_status = 'Pending';
        $exec->save();

        $qcReq = QcRequest::create([
            'qc_request_number'        => 'QC/TEST/' . uniqid(),
            'batch_id'                 => $ctx['batch']->id,
            'batch_stage_execution_id' => $exec->id,
            'stage_name'               => $exec->stage_name,
            'request_date'             => now(),
            'submitted_by'             => $this->admin->id,
            'status'                   => 'Pending',
        ]);

        $evalPending = $this->evaluator->evaluate($exec->fresh());
        $itemPending = collect($evalPending['checklist'])->firstWhere('key', 'qc_approval');
        $this->assertFalse($itemPending['met']);
        $this->assertStringContainsString('Submitted to QC on', $itemPending['reason']);
        $this->assertStringContainsString($qcReq->qc_request_number, $itemPending['reason']);
        $this->assertStringContainsString('Waiting for the QC Manager.', $itemPending['reason']);
        $this->assertStringContainsString('Ask the QC Manager to review it in QC > Pending Inspections.', $itemPending['action']);

        // Rejected
        $qcReq->update(['status' => 'Rejected', 'decision_remarks' => 'LOD above 8%']);
        $exec->update(['qc_status' => 'Rejected']);
        $evalRejected = $this->evaluator->evaluate($exec->fresh());
        $itemRejected = collect($evalRejected['checklist'])->firstWhere('key', 'qc_approval');
        $this->assertStringContainsString('QC rejected this stage: LOD above 8%. The batch is rejected.', $itemRejected['reason']);

        // Rework
        $qcReq->update(['status' => 'Rework Required', 'rework_instructions' => 'Re-filter through 5 micron cloth']);
        $exec->update(['qc_status' => 'Rework Required']);
        $evalRework = $this->evaluator->evaluate($exec->fresh());
        $itemRework = collect($evalRework['checklist'])->firstWhere('key', 'qc_approval');
        $this->assertStringContainsString('QC asked for rework: Re-filter through 5 micron cloth.', $itemRework['reason']);
        $this->assertEquals('Start the rework execution.', $itemRework['action']);
    }

    public function test_supervisor_authorization_missing_explains_why_and_what_to_do_and_summary_line(): void
    {
        $ctx = $this->createBaseStageExecution(['supervisor_auth_required' => true]);

        $eval = $this->evaluator->evaluate($ctx['execution']);
        $item = collect($eval['checklist'])->firstWhere('key', 'supervisor_authorization');

        $this->assertFalse($item['met']);
        $this->assertEquals('Not authorized yet.', $item['reason']);
        $this->assertStringContainsString('Ask a user with the Authorize Stage permission', $item['action']);
        $this->assertStringContainsString('0 of 1 requirements met. To move to the next stage: 1)', $eval['summary']);

        $ctx['execution']->update([
            'supervisor_authorized_by' => $this->admin->id,
            'supervisor_authorized_at' => now(),
        ]);

        $evalDone = $this->evaluator->evaluate($ctx['execution']->fresh());
        $this->assertEquals('All requirements met. Click Complete Stage and Move to Next.', $evalDone['summary']);
    }
}
