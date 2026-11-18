<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockLot;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class InventoryDashboardController extends Controller
{
    public function index()
    {
        $activeLots = StockLot::where('status', 'Active')->where('available_qty', '>', 0)->get();

        $totalMaterials = Material::where('status', 'Active')->count();
        $totalProducts = Product::where('status', 'Active')->count();
        $lowStockCount = $activeLots->where('available_qty', '<=', 10)->count();
        $expiredCount = StockLot::where('expiry_date', '<', now())->where('available_qty', '>', 0)->count();
        $nearExpiryCount = StockLot::whereBetween('expiry_date', [now(), now()->addDays(30)])->where('available_qty', '>', 0)->count();

        // Stock by category
        $categories = ProductCategory::with('products')->get();
        $categoryData = [];
        foreach ($categories as $cat) {
            $prodIds = $cat->products->pluck('id');
            $qty = StockLot::whereIn('product_id', $prodIds)->where('available_qty', '>', 0)->sum('available_qty');
            $categoryData[] = [
                'name' => $cat->name,
                'qty' => (float)$qty,
            ];
        }

        // Warehouse Stock Breakdown
        $warehouses = Warehouse::with(['stockLots' => function ($q) {
            $q->where('available_qty', '>', 0);
        }])->where('status', 'Active')->get();

        // Expiring Soon Lots Table
        $expiringLots = StockLot::with(['material.baseUom', 'product.baseUom', 'warehouse'])
            ->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays(60))
            ->where('available_qty', '>', 0)
            ->orderBy('expiry_date')
            ->take(10)
            ->get();

        return view('inventory.dashboard', compact(
            'totalMaterials',
            'totalProducts',
            'lowStockCount',
            'expiredCount',
            'nearExpiryCount',
            'categoryData',
            'warehouses',
            'expiringLots'
        ));
    }
}
