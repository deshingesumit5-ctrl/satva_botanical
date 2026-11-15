<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Batch;
use App\Models\CreditDebitNote;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\Department;
use App\Models\Dispatch;
use App\Models\Equipment;
use App\Models\Grn;
use App\Models\Material;
use App\Models\MonthlyExpense;
use App\Models\Parameter;
use App\Models\Plant;
use App\Models\ProcessLossTolerance;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductionPlan;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Models\QcRequest;
use App\Models\Recipe;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Shift;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AllRoutesAccessibilityTest extends TestCase
{
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::where('username', 'admin')->first();
    }

    public function test_all_get_routes_return_successful_or_redirect_status_and_never_500()
    {
        $this->actingAs($this->adminUser);

        $parameterResolvers = [
            'role' => fn() => Role::first()?->id ?? 1,
            'user' => fn() => User::first()?->id ?? 1,
            'category' => fn() => ProductCategory::first()?->id ?? 1,
            'plant' => fn() => Plant::first()?->id ?? 1,
            'warehouse' => fn() => Warehouse::first()?->id ?? 1,
            'department' => fn() => Department::first()?->id ?? 1,
            'supplier' => fn() => Supplier::first()?->id ?? 1,
            'customer' => fn() => Customer::first()?->id ?? 1,
            'product' => fn() => Product::first()?->id ?? 1,
            'material' => fn() => Material::first()?->id ?? 1,
            'activity' => fn() => Activity::first()?->id ?? 1,
            'equipment' => fn() => Equipment::first()?->id ?? 1,
            'parameter' => fn() => Parameter::first()?->id ?? 1,
            'shift' => fn() => Shift::first()?->id ?? 1,
            'process_loss_tolerance' => fn() => ProcessLossTolerance::first()?->id ?? 1,
            'tolerance' => fn() => ProcessLossTolerance::first()?->id ?? 1,
            'uom' => fn() => Uom::first()?->id ?? 1,
            'recipe' => fn() => Recipe::first()?->id ?? 1,
            'requisition' => fn() => PurchaseRequisition::first()?->id ?? 1,
            'purchase_order' => fn() => PurchaseOrder::first()?->id ?? 1,
            'grn' => fn() => Grn::first()?->id ?? 1,
            'transfer' => fn() => StockTransfer::first()?->id ?? 1,
            'adjustment' => fn() => StockAdjustment::first()?->id ?? 1,
            'plan' => fn() => ProductionPlan::first()?->id ?? 1,
            'batch' => fn() => Batch::first()?->id ?? 1,
            'qcRequest' => fn() => QcRequest::first()?->id ?? 1,
            'dispatch' => fn() => Dispatch::first()?->id ?? 1,
            'invoice' => fn() => SalesInvoice::first()?->id ?? 1,
            'note' => fn() => CreditDebitNote::first()?->id ?? 1,
            'order' => fn() => SalesOrder::first()?->id ?? 1,
            'payment' => fn() => SupplierPayment::first()?->id ?? 1,
            'purchase_invoice' => fn() => PurchaseInvoice::first()?->id ?? 1,
            'receipt' => fn() => CustomerReceipt::first()?->id ?? 1,
            'expense' => fn() => MonthlyExpense::first()?->id ?? 1,
        ];

        $routes = Route::getRoutes()->get('GET');
        $testedRoutesCount = 0;

        foreach ($routes as $route) {
            $uri = $route->uri();

            // Skip internal / framework routes or wildcard storage routes
            if (str_starts_with($uri, '_') || str_starts_with($uri, 'sanctum') || str_starts_with($uri, 'storage/')) {
                continue;
            }

            // Replace parameters with resolved values
            $resolvedUri = $uri;
            $matches = [];
            preg_match_all('/\{([a-zA-Z0-9_]+)\??\}/', $uri, $matches);

            if (!empty($matches[1])) {
                foreach ($matches[1] as $paramName) {
                    if ($paramName === 'order') {
                        $val = str_contains($uri, 'purchase')
                            ? (PurchaseOrder::first()?->id ?? 1)
                            : (SalesOrder::first()?->id ?? 1);
                    } else {
                        $val = isset($parameterResolvers[$paramName])
                            ? $parameterResolvers[$paramName]()
                            : 1;
                    }
                    $resolvedUri = preg_replace('/\{' . $paramName . '\??\}/', $val, $resolvedUri, 1);
                }
            }

            $response = $this->get('/' . ltrim($resolvedUri, '/'));

            $status = $response->getStatusCode();
            $this->assertNotEquals(
                500,
                $status,
                "Route GET [{$uri}] resolved to [/{$resolvedUri}] failed with 500 error: " . $response->getContent()
            );

            // Must be standard acceptable HTTP response code (200, 301, 302, 303, 307, 404, 403, 405) - never 500
            $this->assertTrue(
                in_array($status, [200, 301, 302, 303, 307, 404, 403]),
                "Route GET [{$uri}] returned unexpected status: {$status}"
            );

            $testedRoutesCount++;
        }

        $this->assertGreaterThan(50, $testedRoutesCount, "Expected at least 50 routes to be tested.");
    }
}
