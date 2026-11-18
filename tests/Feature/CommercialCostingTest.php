<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchCost;
use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\ExpenseAllocation;
use App\Models\Grn;
use App\Models\MonthlyExpense;
use App\Models\Plant;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommercialCostingTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('email', 'admin@gmail.com')->first();
    }

    public function test_can_view_purchase_pages(): void
    {
        $pr = PurchaseRequisition::first();
        $this->actingAs($this->admin)->get(route('purchase.requisitions.index'))->assertStatus(200);
        if ($pr) {
            $this->actingAs($this->admin)->get(route('purchase.requisitions.show', $pr->id))->assertStatus(200);
        }

        $po = PurchaseOrder::first();
        $this->actingAs($this->admin)->get(route('purchase.orders.index'))->assertStatus(200);
        if ($po) {
            $this->actingAs($this->admin)->get(route('purchase.orders.show', $po->id))->assertStatus(200);
        }

        $grn = Grn::first();
        $this->actingAs($this->admin)->get(route('purchase.grn.index'))->assertStatus(200);
        if ($grn) {
            $this->actingAs($this->admin)->get(route('purchase.grn.show', $grn->id))->assertStatus(200);
        }
    }

    public function test_can_view_sales_and_commercial_pages(): void
    {
        $so = SalesOrder::first();
        $this->actingAs($this->admin)->get(route('sales.orders.index'))->assertStatus(200);
        if ($so) {
            $this->actingAs($this->admin)->get(route('sales.orders.show', $so->id))->assertStatus(200);
        }

        $dsp = Dispatch::first();
        $this->actingAs($this->admin)->get(route('sales.dispatch.index'))->assertStatus(200);
        if ($dsp) {
            $this->actingAs($this->admin)->get(route('sales.dispatch.show', $dsp->id))->assertStatus(200);
        }

        $inv = SalesInvoice::first();
        $this->actingAs($this->admin)->get(route('sales.invoices.index'))->assertStatus(200);
        if ($inv) {
            $this->actingAs($this->admin)->get(route('sales.invoices.show', $inv->id))->assertStatus(200);
        }

        $pi = PurchaseInvoice::first();
        $this->actingAs($this->admin)->get(route('sales.purchase-invoices.index'))->assertStatus(200);
        if ($pi) {
            $this->actingAs($this->admin)->get(route('sales.purchase-invoices.show', $pi->id))->assertStatus(200);
        }

        $this->actingAs($this->admin)->get(route('sales.payments.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('sales.receipts.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('sales.notes.index'))->assertStatus(200);
    }

    public function test_can_view_costing_pages(): void
    {
        $this->actingAs($this->admin)->get(route('costing.expenses.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('costing.allocation.index'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('costing.batch-cost.index'))->assertStatus(200);

        $batch = Batch::first();
        if ($batch) {
            $this->actingAs($this->admin)->get(route('costing.batch-cost.show', $batch->id))->assertStatus(200);
        }
    }

    public function test_costing_expense_save_approve_allocate_and_batch_cost_flow(): void
    {
        $plant = Plant::where('status', 'Active')->first();

        // 1. Validation rejects invalid category with 422 JSON
        $invalidRes = $this->actingAs($this->admin)->postJson(route('costing.expenses.store'), [
            'month' => 11,
            'year' => 2026,
            'expense_category' => 'InvalidCategory',
            'amount' => 5000,
        ]);
        $invalidRes->assertStatus(422)->assertJsonValidationErrors(['expense_category']);

        // 2. Save expense via AJAX modal submission
        $storeRes = $this->actingAs($this->admin)->postJson(route('costing.expenses.store'), [
            'month' => 11,
            'year' => 2026,
            'expense_category' => 'Labour',
            'plant_id' => $plant?->id,
            'amount' => 5000,
            'entry_date' => '2026-11-18',
            'description' => 'November plant labour voucher',
        ]);
        $storeRes->assertStatus(200)->assertJson(['success' => true]);

        $expense = MonthlyExpense::where('description', 'November plant labour voucher')->firstOrFail();
        $this->assertEquals('Draft', $expense->status);
        $this->assertEquals('Labour', $expense->expense_category);
        $this->assertEquals(5000.00, (float) $expense->amount);
        $this->assertEquals($this->admin->id, $expense->entered_by);
        $this->assertNotEmpty($expense->expense_number);

        // 3. Index and Show render Draft voucher with Approve button
        $this->actingAs($this->admin)->get(route('costing.expenses.index'))
            ->assertStatus(200)
            ->assertSee($expense->expense_number);

        $this->actingAs($this->admin)->get(route('costing.expenses.show', $expense))
            ->assertStatus(200)
            ->assertSee('Approve Voucher');

        // 4. Approve voucher
        $approveRes = $this->actingAs($this->admin)->post(route('costing.expenses.approve', $expense));
        $approveRes->assertRedirect();
        $expense->refresh();
        $this->assertEquals('Approved', $expense->status);
        $this->assertEquals($this->admin->id, $expense->approved_by);
        $this->assertNotNull($expense->approved_at);

        // 5. Allocation page displays Approved Labour expense ready to allocate
        $this->actingAs($this->admin)->get(route('costing.allocation.index'))
            ->assertStatus(200)
            ->assertSee('Labour')
            ->assertSee('Allocate to Batches');

        // 6. Allocate to completed batches in 11/2026
        $allocRes = $this->actingAs($this->admin)->post(route('costing.allocation.save'), [
            'month' => 11,
            'year' => 2026,
            'expense_category' => 'Labour',
        ]);
        $allocRes->assertRedirect(route('costing.allocation.index'));
        $allocRes->assertSessionHas('success');

        $expense->refresh();
        $this->assertEquals('Posted', $expense->status);
        $this->assertNotNull($expense->posted_at);

        $allocation = ExpenseAllocation::where('month', 11)
            ->where('year', 2026)
            ->where('expense_category', 'Labour')
            ->firstOrFail();
        $this->assertEquals(5000.00, (float) $allocation->total_expense);
        $this->assertGreaterThan(0, $allocation->items()->count());
        $this->assertEquals(5000.00, round((float) $allocation->items()->sum('allocated_amount'), 2));

        // 7. Duplicate allocation blocked (Validation 23)
        $dupRes = $this->actingAs($this->admin)->post(route('costing.allocation.save'), [
            'month' => 11,
            'year' => 2026,
            'expense_category' => 'Labour',
        ]);
        $dupRes->assertRedirect();
        $dupRes->assertSessionHas('error');

        // 8. Batch Cost index, show, and Cost report show non-zero calculated costs
        $completedBatch = Batch::where('status', 'Completed')
            ->whereBetween('production_date', ['2026-11-01', '2026-11-30'])
            ->firstOrFail();

        $batchCost = BatchCost::where('batch_id', $completedBatch->id)->firstOrFail();
        $this->assertGreaterThan(0, (float) $batchCost->labour_cost);
        $this->assertGreaterThan(0, (float) $batchCost->total_batch_cost);

        $this->actingAs($this->admin)->get(route('costing.batch-cost.index'))
            ->assertStatus(200)
            ->assertSee($completedBatch->batch_number);

        $this->actingAs($this->admin)->get(route('costing.batch-cost.show', $completedBatch))
            ->assertStatus(200)
            ->assertSee(number_format($batchCost->total_batch_cost, 2));

        $this->actingAs($this->admin)->get(route('reports.cost'))->assertStatus(200);
        $this->actingAs($this->admin)->get(route('reports.cost.export'))->assertStatus(200);
    }
}
