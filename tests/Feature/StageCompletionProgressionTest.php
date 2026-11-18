<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchParameterReading;
use App\Models\BatchStageExecution;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\QcRequest;
use App\Models\Recipe;
use App\Models\RecipeParameter;
use App\Models\RecipeStage;
use App\Models\RecipeVersion;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Uom;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\ProductionService;
use App\Services\QcService;
use App\Services\RecipeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StageCompletionProgressionTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    protected function createTestBatchWithConfiguredStages(): array
    {
        $product = Product::firstOrFail();
        $uom = Uom::where('uom_code', 'KG')->first() ?? Uom::firstOrFail();
        $stages = Stage::take(3)->get();

        $phParam = Parameter::firstOrCreate(
            ['code' => 'PARAM-PH-TEST'],
            ['name' => 'pH', 'parameter_type' => 'Numeric', 'uom' => 'pH', 'decimal_precision' => 2, 'status' => 'Active']
        );

        $tempParam = Parameter::firstOrCreate(
            ['code' => 'PARAM-TEMP-TEST'],
            ['name' => 'Temperature', 'parameter_type' => 'Numeric', 'uom' => '°C', 'decimal_precision' => 1, 'status' => 'Active']
        );

        $recipe = Recipe::create([
            'recipe_code' => 'RCP-STAGE-TEST-01',
            'name'        => 'Stage Progression Test Recipe',
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

        $recipe->current_version_id = $version->id;
        $recipe->save();

        // Stage 1: Configured with min_repetitions = 3, pH acceptance (1.8-2.0), Temperature >= 60 (hold 30 min), QC required, Supervisor Auth required
        $rs1 = RecipeStage::create([
            'recipe_version_id'           => $version->id,
            'stage_id'                    => $stages[0]->id,
            'sequence_no'                 => 1,
            'occurrence_no'               => 1,
            'is_mandatory'                => true,
            'is_repeatable'               => true,
            'min_repetitions'             => 3,
            'acceptance_parameter_ids'    => [$phParam->id],
            'expected_process_conditions' => [
                [
                    'parameter_id'      => $tempParam->id,
                    'parameter_name'    => 'Temperature',
                    'operator'          => '>=',
                    'value'             => '60',
                    'hold_time_minutes' => 30,
                ],
            ],
            'qc_required'                 => true,
            'supervisor_auth_required'    => true,
        ]);

        $rpPh = RecipeParameter::create([
            'recipe_stage_id'  => $rs1->id,
            'parameter_id'     => $phParam->id,
            'target_value'     => '1.9',
            'min_value'        => '1.8',
            'max_value'        => '2.0',
            'is_mandatory'     => true,
            'deviation_action' => 'Block',
            'display_sequence' => 1,
        ]);

        $rpTemp = RecipeParameter::create([
            'recipe_stage_id'  => $rs1->id,
            'parameter_id'     => $tempParam->id,
            'target_value'     => '65',
            'min_value'        => '60',
            'max_value'        => '75',
            'is_mandatory'     => true,
            'deviation_action' => 'Warning',
            'display_sequence' => 2,
        ]);

        // Stage 2
        $rs2 = RecipeStage::create([
            'recipe_version_id' => $version->id,
            'stage_id'          => $stages[1]->id,
            'sequence_no'       => 2,
            'occurrence_no'     => 1,
            'is_mandatory'      => true,
            'min_repetitions'   => 1,
        ]);

        // Stage 3
        $rs3 = RecipeStage::create([
            'recipe_version_id' => $version->id,
            'stage_id'          => $stages[2]->id,
            'sequence_no'       => 3,
            'occurrence_no'     => 1,
            'is_mandatory'      => true,
            'min_repetitions'   => 1,
        ]);

        $batch = Batch::create([
            'batch_number'       => 'BAT-STAGE-TEST-001',
            'product_id'         => $product->id,
            'recipe_id'          => $recipe->id,
            'recipe_version_id'  => $version->id,
            'planned_qty'        => 100,
            'uom_id'             => $uom->id,
            'production_date'    => now()->toDateString(),
            'planned_start_date' => now()->toDateString(),
            'planned_end_date'   => now()->addDays(2)->toDateString(),
            'supervisor_id'      => $this->superAdmin->id,
            'supervisor_name'    => $this->superAdmin->name,
            'operator_count'     => 2,
            'status'             => 'Planned',
        ]);

        app(ProductionService::class)->startBatch($batch, $this->superAdmin->id);

        return compact('recipe', 'version', 'rs1', 'rs2', 'rs3', 'rpPh', 'rpTemp', 'batch');
    }

    public function test_next_stages_are_locked_at_start_and_direct_url_access_is_blocked(): void
    {
        $data = $this->createTestBatchWithConfiguredStages();
        /** @var Batch $batch */
        $batch = $data['batch'];

        $execs = $batch->stageExecutions()->orderBy('sequence_no')->get();
        $seq1 = $execs->firstWhere('sequence_no', 1);
        $seq2 = $execs->firstWhere('sequence_no', 2);
        $seq3 = $execs->firstWhere('sequence_no', 3);

        // Execution page loads Seq 1 and shows Seq 2 & Seq 3 as Locked with tooltip "Complete Seq 1 first"
        $res = $this->actingAs($this->superAdmin)->get(route('production.execution.show', $batch));
        $res->assertStatus(200);
        $res->assertSee('Stage Completion Requirements');
        $res->assertSee('Complete Seq 1 first');
        $res->assertSee('Locked');

        // Direct URL to Seq 2 via query parameter is blocked with 403
        $lockedQueryRes = $this->actingAs($this->superAdmin)->get(route('production.execution.show', [
            'batch'              => $batch->id,
            'stage_execution_id' => $seq2->id,
        ]));
        $lockedQueryRes->assertStatus(403);
        $lockedQueryRes->assertSee('Complete Seq 1 first');

        // Direct URL to Seq 3 via route parameter is blocked with 403
        $lockedRouteRes = $this->actingAs($this->superAdmin)->get(route('production.execution.stage', [
            'batch'              => $batch->id,
            'stage_execution_id' => $seq3->id,
        ]));
        $lockedRouteRes->assertStatus(403);
        $lockedRouteRes->assertSee('Complete Seq 1 first');

        // Attempting to complete or start a locked stage directly is blocked with 403
        $this->actingAs($this->superAdmin)
            ->post(route('production.execution.complete-stage', ['batch' => $batch->id, 'stageExecution' => $seq2->id]))
            ->assertStatus(403);
    }

    public function test_stage_stays_locked_with_unmet_repetitions_parameter_qc_and_authorization_and_unlocks_when_all_met(): void
    {
        $data = $this->createTestBatchWithConfiguredStages();
        /** @var Batch $batch */
        $batch = $data['batch'];

        $seq1 = $batch->stageExecutions()->where('sequence_no', 1)->firstOrFail();
        $seq2 = $batch->stageExecutions()->where('sequence_no', 2)->firstOrFail();
        $seq3 = $batch->stageExecutions()->where('sequence_no', 3)->firstOrFail();

        // 1. Add Repetition #2 (creates new record, preserves #1)
        $this->actingAs($this->superAdmin)
            ->post(route('production.execution.add-repetition', ['batch' => $batch->id, 'stageExecution' => $seq1->id]))
            ->assertRedirect();

        $rep2 = BatchStageExecution::where('batch_id', $batch->id)
            ->where('sequence_no', 1)
            ->where('execution_number', 2)
            ->firstOrFail();

        // Original execution #1 is preserved
        $this->assertDatabaseHas('batch_stage_executions', [
            'id'               => $seq1->id,
            'execution_number' => 1,
        ]);

        // Enter out-of-range pH 2.2 on Repetition #2 (accepted range 1.8-2.0) and Temperature 65
        $phReading2 = BatchParameterReading::where('batch_stage_execution_id', $rep2->id)
            ->where('parameter_name', 'pH')
            ->firstOrFail();
        $tempReading2 = BatchParameterReading::where('batch_stage_execution_id', $rep2->id)
            ->where('parameter_name', 'Temperature')
            ->firstOrFail();

        $this->actingAs($this->superAdmin)->post(route('production.execution.save-stage', [
            'batch'          => $batch->id,
            'stageExecution' => $rep2->id,
        ]), [
            'actual_hold_time_minutes' => 35,
            'parameters'               => [
                ['id' => $phReading2->id, 'actual_value' => '2.2'],
                ['id' => $tempReading2->id, 'actual_value' => '65'],
            ],
        ])->assertRedirect();

        // Out-of-range reading must remain 2.2 (never auto-corrected)
        $phReading2->refresh();
        $this->assertEquals('2.2', $phReading2->actual_value);
        $this->assertFalse($phReading2->is_within_range);

        // Check checklist on page: shows "Repetitions 2 of 3: not met", "pH 2.2, accepted range 1.8-2.0: not met", "QC approval: pending", "Supervisor authorization: not authorized"
        $pageRes = $this->actingAs($this->superAdmin)->get(route('production.execution.show', [
            'batch'              => $batch->id,
            'stage_execution_id' => $rep2->id,
        ]));
        $pageRes->assertStatus(200);
        $pageRes->assertSee('Repetitions 2 of 3: not met');
        $pageRes->assertSee('pH 2.2, accepted range 1.8-2.0: not met');
        $pageRes->assertSee('QC approval: pending');
        $pageRes->assertSee('Supervisor authorization: not authorized');

        // Attempting to complete stage with 2 of 3 repetitions fails and keeps Seq 2 locked
        $this->actingAs($this->superAdmin)->post(route('production.execution.complete-stage', [
            'batch'          => $batch->id,
            'stageExecution' => $rep2->id,
        ]));
        $this->assertTrue(app(ProductionService::class)->getStageLockState($batch, $seq2)['locked']);

        // 2. Add Repetition #3 (now 3 of 3 repetitions exist)
        $this->actingAs($this->superAdmin)
            ->post(route('production.execution.add-repetition', ['batch' => $batch->id, 'stageExecution' => $rep2->id]))
            ->assertRedirect();

        $rep3 = BatchStageExecution::where('batch_id', $batch->id)
            ->where('sequence_no', 1)
            ->where('execution_number', 3)
            ->firstOrFail();

        $phReading3 = BatchParameterReading::where('batch_stage_execution_id', $rep3->id)
            ->where('parameter_name', 'pH')
            ->firstOrFail();
        $tempReading3 = BatchParameterReading::where('batch_stage_execution_id', $rep3->id)
            ->where('parameter_name', 'Temperature')
            ->firstOrFail();

        // Enter in-range pH 1.9 and Temperature 65 with hold time 35 min
        $this->actingAs($this->superAdmin)->post(route('production.execution.save-stage', [
            'batch'          => $batch->id,
            'stageExecution' => $rep3->id,
        ]), [
            'actual_hold_time_minutes' => 35,
            'parameters'               => [
                ['id' => $phReading3->id, 'actual_value' => '1.9'],
                ['id' => $tempReading3->id, 'actual_value' => '65'],
            ],
        ])->assertRedirect();

        // Still not authorized by supervisor -> completeStage fails and Seq 2 stays locked
        $this->actingAs($this->superAdmin)->post(route('production.execution.complete-stage', [
            'batch'          => $batch->id,
            'stageExecution' => $rep3->id,
        ]));
        $rep3->refresh();
        $this->assertEquals('In Progress', $rep3->status);
        $this->assertTrue(app(ProductionService::class)->getStageLockState($batch, $seq2)['locked']);

        // 3. Verify operator without "Authorize Stage" permission cannot self-authorize
        $operatorRole = Role::create([
            'role_code' => 'line_operator_no_auth',
            'role_name' => 'Line Operator No Auth',
            'status'    => 'Active',
        ]);
        PermissionService::syncRolePermissions($operatorRole, ['production.execution.view', 'production.execution.edit']);
        $operatorUser = User::create([
            'user_id'  => 'USR-OP-991',
            'name'     => 'Operator Without Auth',
            'email'    => 'op991@sattvabotanical.local',
            'username' => 'op991',
            'password' => 'Operator@123',
            'role_id'  => $operatorRole->id,
            'status'   => 'Active',
        ]);

        $this->actingAs($operatorUser)
            ->post(route('production.execution.authorize-stage', ['batch' => $batch->id, 'stageExecution' => $rep3->id]))
            ->assertStatus(403);

        // 4. Supervisor with "Authorize Stage" permission authorizes the stage
        $this->actingAs($this->superAdmin)
            ->post(route('production.execution.authorize-stage', ['batch' => $batch->id, 'stageExecution' => $rep3->id]))
            ->assertRedirect();

        $rep3->refresh();
        $this->assertNotNull($rep3->supervisor_authorized_by);
        $this->assertNotNull($rep3->supervisor_authorized_at);

        // 5. Complete Stage -> Since QC is required, stage goes to QC Pending and Seq 2 STAYS LOCKED until QC Approves
        $this->actingAs($this->superAdmin)
            ->post(route('production.execution.complete-stage', ['batch' => $batch->id, 'stageExecution' => $rep3->id]))
            ->assertRedirect();

        $rep3->refresh();
        $this->assertEquals('QC Pending', $rep3->status);
        $this->assertTrue(app(ProductionService::class)->getStageLockState($batch, $seq2)['locked']);

        // Direct URL to Seq 2 is STILL blocked while QC is pending
        $this->actingAs($this->superAdmin)
            ->get(route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $seq2->id]))
            ->assertStatus(403);

        // 6. QC Approves -> Stage 1 becomes Completed and Seq 2 UNLOCKS!
        $qcReq = QcRequest::where('batch_stage_execution_id', $rep3->id)->firstOrFail();
        app(QcService::class)->approve($qcReq, $this->superAdmin->id, 'All QC specs passed');

        $rep3->refresh();
        $seq2->refresh();
        $this->assertEquals('Completed', $rep3->status);
        $this->assertEquals('Approved', $rep3->qc_status);
        $this->assertFalse(app(ProductionService::class)->getStageLockState($batch, $seq2)['locked']);
        $this->assertEquals('In Progress', $seq2->status);

        // Direct URL to Seq 2 now succeeds (200) while Seq 3 is still locked (Complete Seq 2 first)
        $this->actingAs($this->superAdmin)
            ->get(route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $seq2->id]))
            ->assertStatus(200);

        $lockedSeq3 = $this->actingAs($this->superAdmin)
            ->get(route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $seq3->id]));
        $lockedSeq3->assertStatus(403);
        $lockedSeq3->assertSee('Complete Seq 2 first');

        // 7. Complete Seq 2 and Seq 3 -> Completing the last stage makes batch ready for Reconciliation (no auto-close)
        $this->actingAs($this->superAdmin)
            ->post(route('production.execution.complete-stage', ['batch' => $batch->id, 'stageExecution' => $seq2->id]))
            ->assertRedirect();

        $seq3->refresh();
        $this->assertFalse(app(ProductionService::class)->getStageLockState($batch, $seq3)['locked']);

        $this->actingAs($this->superAdmin)
            ->post(route('production.execution.complete-stage', ['batch' => $batch->id, 'stageExecution' => $seq3->id]))
            ->assertRedirect();

        $seq3->refresh();
        $batch->refresh();
        $this->assertEquals('Completed', $seq3->status);
        $this->assertEquals('In Progress', $batch->status); // Ready for reconciliation, not auto-closed
    }

    public function test_recipe_version_cloning_copies_completion_requirements(): void
    {
        $data = $this->createTestBatchWithConfiguredStages();
        /** @var RecipeVersion $version */
        $version = $data['version'];

        $newVersion = app(RecipeService::class)->createNewVersion($version, 'V2.0', $this->superAdmin->id);
        $clonedStage1 = $newVersion->stages()->where('sequence_no', 1)->firstOrFail();

        $this->assertEquals(3, $clonedStage1->min_repetitions);
        $this->assertTrue($clonedStage1->qc_required);
        $this->assertTrue($clonedStage1->supervisor_auth_required);
        $this->assertNotEmpty($clonedStage1->acceptance_parameter_ids);
        $this->assertNotEmpty($clonedStage1->expected_process_conditions);
    }
}
