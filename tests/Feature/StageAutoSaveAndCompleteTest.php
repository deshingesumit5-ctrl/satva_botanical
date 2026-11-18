<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchActivityExecution;
use App\Models\BatchParameterReading;
use App\Models\Activity;
use App\Models\Parameter;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeParameter;
use App\Models\RecipeStage;
use App\Models\RecipeStageActivity;
use App\Models\RecipeVersion;
use App\Models\Role;
use App\Models\Stage;
use App\Models\Uom;
use App\Models\User;
use App\Services\PermissionService;
use App\Services\ProductionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class StageAutoSaveAndCompleteTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    protected function createConfiguredExecution(): array
    {
        $product = Product::firstOrFail();
        $uom = Uom::firstOrFail();
        $stages = Stage::take(2)->get();

        $act = Activity::firstOrCreate(['code' => 'ACT-AUTO-SAVE'], ['name' => 'Cleaning', 'status' => 'Active']);
        $ph = Parameter::firstOrCreate(['code' => 'PARAM-PH-AS'], ['name' => 'pH', 'parameter_type' => 'Numeric', 'uom' => 'pH', 'status' => 'Active']);

        $recipe = Recipe::create([
            'recipe_code' => 'RCP-AS-' . uniqid(),
            'name'        => 'Auto Save Recipe',
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

        $rs1 = RecipeStage::create([
            'recipe_version_id'        => $version->id,
            'stage_id'                 => $stages[0]->id,
            'sequence_no'              => 1,
            'occurrence_no'            => 1,
            'is_mandatory'             => true,
            'acceptance_parameter_ids' => [$ph->id],
        ]);

        RecipeStageActivity::create([
            'recipe_stage_id' => $rs1->id,
            'activity_id'     => $act->id,
            'sequence_no'     => 1,
            'is_mandatory'    => true,
        ]);

        RecipeParameter::create([
            'recipe_stage_id'  => $rs1->id,
            'parameter_id'     => $ph->id,
            'target_value'     => '1.9',
            'min_value'        => '1.8',
            'max_value'        => '2.0',
            'is_mandatory'     => true,
            'deviation_action' => 'Block',
        ]);

        RecipeStage::create([
            'recipe_version_id' => $version->id,
            'stage_id'          => $stages[1]->id,
            'sequence_no'       => 2,
            'occurrence_no'     => 1,
            'is_mandatory'      => true,
        ]);

        $batch = Batch::create([
            'batch_number'       => 'BAT-AS-' . uniqid(),
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

        $seq1 = $batch->stageExecutions()->where('sequence_no', 1)->firstOrFail();
        $seq2 = $batch->stageExecutions()->where('sequence_no', 2)->firstOrFail();

        return compact('batch', 'seq1', 'seq2');
    }

    public function test_save_stage_log_button_is_removed_and_panel_explains_unmet_reasons(): void
    {
        $ctx = $this->createConfiguredExecution();
        $res = $this->actingAs($this->admin)->get(route('production.execution.show', $ctx['batch']));
        $res->assertStatus(200);

        $res->assertDontSee('Save Stage Log');
        $res->assertSee('Why:');
        $res->assertSee('What to do:');
        $res->assertSee("Activity 'Cleaning' has no start and end time.");
        $res->assertSee("Reading for 'pH' is missing.");
        $res->assertSee('Go to Section B');
        $res->assertSee('Go to Section C');
    }

    public function test_autosave_persists_field_and_updates_checklist_live(): void
    {
        $ctx = $this->createConfiguredExecution();
        $seq1 = $ctx['seq1'];
        $reading = BatchParameterReading::where('batch_stage_execution_id', $seq1->id)->firstOrFail();

        $res = $this->actingAs($this->admin)->postJson(
            route('production.execution.save-stage', ['batch' => $ctx['batch']->id, 'stageExecution' => $seq1->id]),
            [
                'supervisor_remarks' => 'Auto saved note',
                'parameters'         => [
                    ['id' => $reading->id, 'actual_value' => '2.2'],
                ],
            ]
        );

        $res->assertStatus(200)
            ->assertJson(['success' => true, 'saved' => true]);

        $reading->refresh();
        $seq1->refresh();
        $this->assertEquals('2.2', $reading->actual_value);
        $this->assertEquals('Auto saved note', $seq1->supervisor_remarks);
        $this->assertStringContainsString('pH entered 2.2, accepted range 1.8-2.0.', $res->json('checklist_html'));
    }

    public function test_complete_when_missing_saves_data_and_does_not_complete(): void
    {
        $ctx = $this->createConfiguredExecution();
        $seq1 = $ctx['seq1'];
        $reading = BatchParameterReading::where('batch_stage_execution_id', $seq1->id)->firstOrFail();

        // Activity has no start/end times, pH is 1.9
        $res = $this->actingAs($this->admin)->postJson(
            route('production.execution.complete-stage', ['batch' => $ctx['batch']->id, 'stageExecution' => $seq1->id]),
            [
                'supervisor_remarks' => 'Entered during complete click',
                'parameters'         => [
                    ['id' => $reading->id, 'actual_value' => '1.9'],
                ],
            ]
        );

        $res->assertStatus(422)
            ->assertJson([
                'saved'     => true,
                'completed' => false,
                'message'   => 'Cannot complete yet: 1 requirement(s) not met',
            ]);

        $seq1->refresh();
        $reading->refresh();
        $this->assertEquals('In Progress', $seq1->status);
        $this->assertEquals('Entered during complete click', $seq1->supervisor_remarks);
        $this->assertEquals('1.9', $reading->actual_value);
    }

    public function test_complete_saves_unsaved_values_and_completes_when_all_met(): void
    {
        $ctx = $this->createConfiguredExecution();
        $seq1 = $ctx['seq1'];
        $seq2 = $ctx['seq2'];
        $actExec = BatchActivityExecution::where('batch_stage_execution_id', $seq1->id)->firstOrFail();
        $reading = BatchParameterReading::where('batch_stage_execution_id', $seq1->id)->firstOrFail();

        $res = $this->actingAs($this->admin)->postJson(
            route('production.execution.complete-stage', ['batch' => $ctx['batch']->id, 'stageExecution' => $seq1->id]),
            [
                'activities' => [
                    $actExec->id => [
                        'id'             => $actExec->id,
                        'start_datetime' => now()->subMinutes(15)->format('Y-m-d\TH:i'),
                        'end_datetime'   => now()->format('Y-m-d\TH:i'),
                    ],
                ],
                'parameters' => [
                    ['id' => $reading->id, 'actual_value' => '1.9'],
                ],
            ]
        );

        $res->assertStatus(200)
            ->assertJson(['success' => true, 'completed' => true]);

        $seq1->refresh();
        $seq2->refresh();
        $this->assertEquals('Completed', $seq1->status);
        $this->assertEquals('In Progress', $seq2->status);
    }

    public function test_completed_stage_is_read_only_and_rejects_autosave(): void
    {
        $ctx = $this->createConfiguredExecution();
        $seq1 = $ctx['seq1'];
        $seq1->update(['status' => 'Completed']);

        $page = $this->actingAs($this->admin)->get(route('production.execution.show', [
            'batch'              => $ctx['batch']->id,
            'stage_execution_id' => $seq1->id,
        ]));
        $page->assertStatus(200)->assertSee('Read-only: Stage is Completed.');

        $this->actingAs($this->admin)->postJson(
            route('production.execution.save-stage', ['batch' => $ctx['batch']->id, 'stageExecution' => $seq1->id]),
            ['supervisor_remarks' => 'Should not save']
        )->assertStatus(422);
    }

    public function test_completed_stage_does_not_auto_create_pending_activities_and_unlocks_stage_2(): void
    {
        $ctx = $this->createConfiguredExecution();
        $seq1 = $ctx['seq1'];
        $seq2 = $ctx['seq2'];

        // Simulate a completed stage (e.g. seeded/completed) without activity rows
        BatchActivityExecution::where('batch_stage_execution_id', $seq1->id)->delete();
        BatchParameterReading::where('batch_stage_execution_id', $seq1->id)->delete();
        $seq1->update(['status' => 'Completed', 'completed_at' => now()]);

        $page = $this->actingAs($this->admin)->get(route('production.execution.show', [
            'batch'              => $ctx['batch']->id,
            'stage_execution_id' => $seq1->id,
        ]));

        $page->assertStatus(200);
        $page->assertDontSee(': not met');
        $page->assertDontSee('Why:');
        $page->assertSee('Stage completed');
        $page->assertSee('Go to Seq 2');

        // Must NOT have inserted any Pending activity row on the Completed stage
        $this->assertEquals(0, BatchActivityExecution::where('batch_stage_execution_id', $seq1->id)->count());

        // Stage 2 must be unlocked and accessible
        $this->actingAs($this->admin)->get(route('production.execution.show', [
            'batch'              => $ctx['batch']->id,
            'stage_execution_id' => $seq2->id,
        ]))->assertStatus(200);
    }
}
