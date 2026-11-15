<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\StockLot;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

class InventoryStockTest extends TestCase
{
    protected $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::where('username', 'admin')->first();
    }

    public function test_can_view_inventory_dashboard_and_stock_pages(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.dashboard'));
        $response->assertStatus(200);

        $stockResponse = $this->actingAs($this->admin)->get(route('inventory.stock.index'));
        $stockResponse->assertStatus(200);

        $lotsResponse = $this->actingAs($this->admin)->get(route('inventory.stock.lots'));
        $lotsResponse->assertStatus(200);

        $ledgerResponse = $this->actingAs($this->admin)->get(route('inventory.stock.ledger'));
        $ledgerResponse->assertStatus(200);
    }

    public function test_can_view_and_create_stock_transfer(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.transfers.index'));
        $response->assertStatus(200);

        $createResponse = $this->actingAs($this->admin)->get(route('inventory.transfers.create'));
        $createResponse->assertStatus(200);

        $wh1 = Warehouse::first();
        $wh2 = Warehouse::skip(1)->first() ?? $wh1;
        $lot = StockLot::first();

        if ($lot) {
            $storeResponse = $this->actingAs($this->admin)->post(route('inventory.transfers.store'), [
                'from_warehouse_id' => $wh1->id,
                'to_warehouse_id' => $wh2->id,
                'stock_lot_id' => $lot->id,
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'quantity' => 10.0,
                'remarks' => 'Inter-warehouse movement test',
            ]);
            $storeResponse->assertRedirect();
        }
    }

    public function test_can_view_and_create_stock_adjustment(): void
    {
        $response = $this->actingAs($this->admin)->get(route('inventory.adjustments.index'));
        $response->assertStatus(200);

        $createResponse = $this->actingAs($this->admin)->get(route('inventory.adjustments.create'));
        $createResponse->assertStatus(200);

        $lot = StockLot::first();
        if ($lot) {
            $storeResponse = $this->actingAs($this->admin)->post(route('inventory.adjustments.store'), [
                'warehouse_id' => $lot->warehouse_id,
                'stock_lot_id' => $lot->id,
                'material_id' => $lot->material_id,
                'product_id' => $lot->product_id,
                'adjustment_type' => 'Write-Off',
                'quantity' => 5.0,
                'reason' => 'Physical stock count variance adjustment test',
            ]);
            $storeResponse->assertRedirect();
        }
    }

    public function test_can_view_wip_recovery_and_waste_registers(): void
    {
        $wipResponse = $this->actingAs($this->admin)->get(route('inventory.wip.index'));
        $wipResponse->assertStatus(200);

        $recoveryResponse = $this->actingAs($this->admin)->get(route('inventory.recovery.index'));
        $recoveryResponse->assertStatus(200);

        $wasteResponse = $this->actingAs($this->admin)->get(route('inventory.waste.index'));
        $wasteResponse->assertStatus(200);
    }
}
