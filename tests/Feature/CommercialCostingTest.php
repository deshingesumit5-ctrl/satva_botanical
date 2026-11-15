<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\Grn;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class CommercialCostingTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
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
}
