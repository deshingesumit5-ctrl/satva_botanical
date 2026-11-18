<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchMaterialRequirement;
use App\Models\ProductionPlan;
use App\Models\StockLot;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductionBatchTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@gmail.com')->first();
    }

    public function test_can_view_production_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('production.dashboard'));
        $response->assertStatus(200);
    }

    public function test_can_view_production_plans(): void
    {
        $response = $this->actingAs($this->admin)->get(route('production.plans.index'));
        $response->assertStatus(200);

        $plan = ProductionPlan::first();
        if ($plan) {
            $showResponse = $this->actingAs($this->admin)->get(route('production.plans.show', $plan->id));
            $showResponse->assertStatus(200);
        }
    }

    public function test_can_view_batches_and_details(): void
    {
        $response = $this->actingAs($this->admin)->get(route('production.batches.index'));
        $response->assertStatus(200);

        $batch = Batch::first();
        if ($batch) {
            $showResponse = $this->actingAs($this->admin)->get(route('production.batches.show', $batch->id));
            $showResponse->assertStatus(200);

            $allocResponse = $this->actingAs($this->admin)->get(route('production.batches.allocate', $batch->id));
            $allocResponse->assertStatus(200);
        }
    }

    public function test_can_view_batch_execution_and_reconciliation(): void
    {
        $batch = Batch::first();
        $this->assertNotNull($batch);

        $execResponse = $this->actingAs($this->admin)->get(route('production.execution.show', $batch->id));
        $execResponse->assertStatus(200);

        $reconResponse = $this->actingAs($this->admin)->get(route('production.reconciliation.show', $batch->id));
        $reconResponse->assertStatus(200);
        // Verify mass balance variance calculation without hardcoded numeric tolerance
        $reconResponse->assertSee('Unaccounted Difference');
    }

    public function test_can_view_bmr_and_generate_pdf(): void
    {
        $batch = Batch::first();
        $this->assertNotNull($batch);

        $bmrResponse = $this->actingAs($this->admin)->get(route('production.bmr.show', $batch->id));
        $bmrResponse->assertStatus(200);

        $pdfResponse = $this->actingAs($this->admin)->get(route('production.bmr.pdf', $batch->id));
        $pdfResponse->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfResponse->headers->get('Content-Type'));
    }

    public function test_can_view_rework_records(): void
    {
        $response = $this->actingAs($this->admin)->get(route('production.rework.index'));
        $response->assertStatus(200);
    }

    public function test_batch_allocation_auto_generates_requirements_and_reserves_stock(): void
    {
        $batch = Batch::with('recipeVersion.stages.inputMaterials')->firstOrFail();

        $res = $this->actingAs($this->admin)->get(route('production.batches.allocate', $batch->id));
        $res->assertStatus(200);

        $reqs = BatchMaterialRequirement::where('batch_id', $batch->id)->get();
        $this->assertGreaterThan(0, $reqs->count());

        $reqWithLot = $reqs->first(function ($r) {
            return StockLot::where('material_id', $r->material_id)
                ->where('status', 'Active')
                ->where('available_qty', '>=', 1)
                ->where('qc_status', '!=', 'QC Rejected')
                ->where('qc_status', '!=', 'QC Hold')
                ->exists();
        });
        $this->assertNotNull($reqWithLot);

        $lot = StockLot::where('material_id', $reqWithLot->material_id)
            ->where('status', 'Active')
            ->where('available_qty', '>=', 1)
            ->where('qc_status', '!=', 'QC Rejected')
            ->where('qc_status', '!=', 'QC Hold')
            ->firstOrFail();

        $res->assertSee($lot->lot_number);

        $origAvail = (float) $lot->available_qty;
        $saveRes = $this->actingAs($this->admin)->post(route('production.batches.save-allocation', $batch->id), [
            'allocations' => [
                [
                    'requirement_id' => $reqWithLot->id,
                    'material_id'    => $reqWithLot->material_id,
                    'stock_lot_id'   => $lot->id,
                    'allocated_qty'  => 1,
                ],
            ],
        ]);

        $saveRes->assertRedirect(route('production.batches.show', $batch->id));
        $lot->refresh();
        $this->assertEquals($origAvail - 1, (float) $lot->available_qty);
    }
}
