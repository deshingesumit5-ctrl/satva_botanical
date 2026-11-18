<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchCost;
use App\Models\ProductionPlan;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockLot;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DashboardManagementMetricsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@gmail.com')->firstOrFail();
    }

    public function test_main_dashboard_displays_all_18_management_metrics_from_real_db_and_no_data_available(): void
    {
        $res = $this->actingAs($this->admin)->get(route('dashboard'));
        $res->assertStatus(200);

        $expectedLabels = [
            'Total Production Plans',
            'Total Batches',
            'Running Batches',
            'Completed Batches',
            'On-Hold Batches',
            'QC Pending',
            'Rework Required',
            'Rejected Batches',
            'Total Production Quantity',
            'Finished Goods Stock',
            'Raw Material Stock',
            'WIP Stock',
            'Recovered Solvent Stock',
            'Saleable By-product Stock',
            'Waste / Spent Quantity',
            'Purchase Pending',
            'Sales Pending',
            'Monthly Production Cost',
        ];

        foreach ($expectedLabels as $label) {
            $res->assertSee($label);
        }

        // Real DB totals should match (not hardcoded)
        $res->assertSee(number_format(ProductionPlan::count()));
        $res->assertSee(number_format(Batch::count()));
        $res->assertSee('No data available');

        // Confirmed / In Progress order count should reflect real SalesOrder count
        $confirmedOrInProg = SalesOrder::whereIn('status', ['Confirmed', 'In Progress', 'Approved'])->count();
        if ($confirmedOrInProg > 0) {
            $res->assertSee('Confirmed / In Progress');
        }
    }

    public function test_dashboard_date_filter_with_no_matching_records_shows_no_data_available(): void
    {
        $res = $this->actingAs($this->admin)->get(route('dashboard', [
            'from_date' => '2099-01-01',
            'to_date'   => '2099-01-31',
        ]));

        $res->assertStatus(200);
        $res->assertSee('No data available');
    }
}
