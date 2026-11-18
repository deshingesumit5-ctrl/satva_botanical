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
use App\Models\Stage;
use App\Models\Uom;
use App\Models\User;
use App\Services\ProductionService;
use App\Services\QcService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class QcWorkflowAndHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    protected function createTwoStageBatchWithQcOnStage1(): array
    {
        $product = Product::firstOrFail();
        $uom = Uom::firstOrFail();
        $stages = Stage::take(2)->get();

        $phParam = Parameter::firstOrCreate(
            ['code' => 'PARAM-PH-QCWF'],
            ['name' => 'pH', 'parameter_type' => 'Numeric', 'uom' => 'pH', 'status' => 'Active']
        );

        $recipe = Recipe::create([
            'recipe_code' => 'RCP-QCWF-' . uniqid(),
            'name'        => 'QC Workflow Test Recipe',
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

        // Deliberately NO recipe QC tests mapped on Stage 1 to verify QcRequest is still created with snapshot
        $rs1 = RecipeStage::create([
            'recipe_version_id' => $version->id,
            'stage_id'          => $stages[0]->id,
            'sequence_no'       => 1,
            'occurrence_no'     => 1,
            'is_mandatory'      => true,
            'qc_required'       => true,
        ]);

        RecipeParameter::create([
            'recipe_stage_id' => $rs1->id,
            'parameter_id'    => $phParam->id,
            'target_value'    => '2.0',
            'min_value'       => '1.8',
            'max_value'       => '2.2',
            'is_mandatory'    => true,
        ]);

        $rs2 = RecipeStage::create([
            'recipe_version_id' => $version->id,
            'stage_id'          => $stages[1]->id,
            'sequence_no'       => 2,
            'occurrence_no'     => 1,
            'is_mandatory'      => true,
        ]);

        $batch = Batch::create([
            'batch_number'       => 'BAT-QCWF-' . uniqid(),
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
            'status'             => 'Planned',
        ]);

        app(ProductionService::class)->startBatch($batch, $this->admin->id);

        return compact('batch', 'rs1', 'rs2');
    }

    public function test_submit_stage_to_qc_appears_in_all_qc_screens_and_approve_unlocks_next_stage(): void
    {
        $data = $this->createTwoStageBatchWithQcOnStage1();
        $batch = $data['batch'];
        $seq1 = $batch->stageExecutions()->where('sequence_no', 1)->firstOrFail();
        $seq2 = $batch->stageExecutions()->where('sequence_no', 2)->firstOrFail();
        $reading = BatchParameterReading::where('batch_stage_execution_id', $seq1->id)->firstOrFail();

        $completeRes = $this->actingAs($this->admin)->postJson(
            route('production.execution.complete-stage', ['batch' => $batch->id, 'stageExecution' => $seq1->id]),
            [
                'output_quantity' => 85,
                'parameters'      => [
                    ['id' => $reading->id, 'actual_value' => '1.95'],
                ],
            ]
        );
        $completeRes->assertStatus(200)->assertJson(['completed' => true, 'qc_pending' => true]);

        $qcReq = QcRequest::where('batch_stage_execution_id', $seq1->id)->firstOrFail();
        $this->assertEquals('Pending', $qcReq->status);
        $this->assertEquals($batch->product_id, $qcReq->product_id);
        $this->assertNotEmpty($qcReq->readings_snapshot['parameters']);
        $this->assertDatabaseHas('notifications', [
            'role_code' => 'qc_manager',
            'title'     => 'QC Pending',
            'type'      => 'qc_pending',
        ]);

        // Pending Inspections
        $pendingPage = $this->actingAs($this->admin)->get(route('qc.pending'));
        $pendingPage->assertStatus(200)->assertSee($qcReq->qc_request_number)->assertSee($batch->batch_number);

        // QC Dashboard counters & recent list
        $dashPage = $this->actingAs($this->admin)->get(route('qc.dashboard'));
        $dashPage->assertStatus(200)->assertSee($qcReq->qc_request_number);

        // Production > QC Pending Batches
        $batchesPendingPage = $this->actingAs($this->admin)->get(route('production.batches.index', ['status' => 'QC Pending']));
        $batchesPendingPage->assertStatus(200)->assertSee($batch->batch_number);

        // Batch Show page & Execution Show page show link to QC Request
        $this->actingAs($this->admin)->get(route('production.batches.show', $batch))
            ->assertStatus(200)
            ->assertSee($qcReq->qc_request_number);

        $this->actingAs($this->admin)->get(route('production.execution.show', ['batch' => $batch->id, 'stage_execution_id' => $seq1->id]))
            ->assertStatus(200)
            ->assertSee($qcReq->qc_request_number);

        // QC History lists Pending request + Last updated
        $historyPending = $this->actingAs($this->admin)->get(route('qc.history'));
        $historyPending->assertStatus(200)
            ->assertSee($qcReq->qc_request_number)
            ->assertSee('Pending')
            ->assertSee('Last updated:')
            ->assertSee('1.95');

        // Approve QC Request -> decision in History and Seq 2 unlocks
        $this->actingAs($this->admin)->post(route('qc.approve', $qcReq), [
            'remarks' => 'Verified pH and yield',
        ])->assertRedirect(route('qc.pending'));

        $seq2->refresh();
        $this->assertEquals('In Progress', $seq2->status);

        $historyApproved = $this->actingAs($this->admin)->get(route('qc.history'));
        $historyApproved->assertStatus(200)
            ->assertSee($qcReq->qc_request_number)
            ->assertSee('Approved')
            ->assertSee('Verified pH and yield');
    }

    public function test_qc_reject_and_rework_paths_work_and_appear_in_history(): void
    {
        // 1. Reject path
        $data1 = $this->createTwoStageBatchWithQcOnStage1();
        $batch1 = $data1['batch'];
        $seq1 = $batch1->stageExecutions()->where('sequence_no', 1)->firstOrFail();
        $reading1 = BatchParameterReading::where('batch_stage_execution_id', $seq1->id)->firstOrFail();

        $this->actingAs($this->admin)->postJson(
            route('production.execution.complete-stage', ['batch' => $batch1->id, 'stageExecution' => $seq1->id]),
            ['parameters' => [['id' => $reading1->id, 'actual_value' => '1.9']]]
        )->assertStatus(200);

        $qcReq1 = QcRequest::where('batch_stage_execution_id', $seq1->id)->firstOrFail();
        $this->actingAs($this->admin)->post(route('qc.reject', $qcReq1), [
            'reason' => 'Contamination detected',
        ])->assertRedirect(route('qc.pending'));

        $batch1->refresh();
        $this->assertEquals('Rejected', $batch1->status);
        $this->actingAs($this->admin)->get(route('qc.history'))
            ->assertSee($qcReq1->qc_request_number)
            ->assertSee('Rejected')
            ->assertSee('Contamination detected');

        // 2. Rework + Retest path
        $data2 = $this->createTwoStageBatchWithQcOnStage1();
        $batch2 = $data2['batch'];
        $seq2Stage1 = $batch2->stageExecutions()->where('sequence_no', 1)->firstOrFail();
        $reading2 = BatchParameterReading::where('batch_stage_execution_id', $seq2Stage1->id)->firstOrFail();

        $this->actingAs($this->admin)->postJson(
            route('production.execution.complete-stage', ['batch' => $batch2->id, 'stageExecution' => $seq2Stage1->id]),
            ['parameters' => [['id' => $reading2->id, 'actual_value' => '1.9']]]
        )->assertStatus(200);

        $qcReq2 = QcRequest::where('batch_stage_execution_id', $seq2Stage1->id)->firstOrFail();
        $this->actingAs($this->admin)->post(route('qc.rework', $qcReq2), [
            'reason'       => 'Cloudy extract',
            'instructions' => 'Re-centrifuge for 20 minutes',
        ])->assertRedirect();

        $reworkExec = BatchStageExecution::where('batch_id', $batch2->id)
            ->where('execution_type', 'Rework')
            ->firstOrFail();
        $retestReading = BatchParameterReading::where('batch_stage_execution_id', $reworkExec->id)->firstOrFail();

        $this->actingAs($this->admin)->postJson(
            route('production.execution.complete-stage', ['batch' => $batch2->id, 'stageExecution' => $reworkExec->id]),
            ['parameters' => [['id' => $retestReading->id, 'actual_value' => '2.0']]]
        )->assertStatus(200);

        $retestQc = QcRequest::where('batch_stage_execution_id', $reworkExec->id)->firstOrFail();
        $this->assertEquals($qcReq2->id, $retestQc->parent_qc_request_id);
        $this->actingAs($this->admin)->post(route('qc.approve', $retestQc), [
            'remarks' => 'Retest clear and passed',
        ])->assertRedirect(route('qc.pending'));

        $this->actingAs($this->admin)->get(route('qc.history'))
            ->assertSee($qcReq2->qc_request_number)
            ->assertSee('Re-centrifuge for 20 minutes')
            ->assertSee($retestQc->qc_request_number)
            ->assertSee('Retest clear and passed');
    }

    public function test_backfill_command_creates_missing_qc_request_idempotently(): void
    {
        $data = $this->createTwoStageBatchWithQcOnStage1();
        $batch = $data['batch'];
        $seq1 = $batch->stageExecutions()->where('sequence_no', 1)->firstOrFail();

        // Simulate legacy stage left in QC Pending with no QcRequest
        $seq1->update(['status' => 'QC Pending', 'qc_status' => 'Pending']);
        $this->assertDatabaseMissing('qc_requests', ['batch_stage_execution_id' => $seq1->id]);

        Artisan::call('qc:backfill-missing-requests');
        $this->assertDatabaseHas('qc_requests', [
            'batch_stage_execution_id' => $seq1->id,
            'status'                   => 'Pending',
        ]);

        $countAfterFirst = QcRequest::where('batch_stage_execution_id', $seq1->id)->count();
        $this->assertEquals(1, $countAfterFirst);

        // Running twice must not duplicate
        Artisan::call('qc:backfill-missing-requests');
        $this->assertEquals(1, QcRequest::where('batch_stage_execution_id', $seq1->id)->count());
    }
}
