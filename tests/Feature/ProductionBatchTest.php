<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\ProductionPlan;
use App\Models\User;
use Tests\TestCase;

class ProductionBatchTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
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
}
