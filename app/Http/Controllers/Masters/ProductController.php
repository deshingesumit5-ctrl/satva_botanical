<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Uom;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['category', 'baseUom']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('product_code', 'like', "%{$search}%")
                    ->orWhere('hsn_code', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('product_category_id', $categoryId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $products = $query->latest()->paginate($perPage)->withQueryString();
        $categories = ProductCategory::where('status', 'Active')->get();

        return view('masters.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        $categories = ProductCategory::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();
        return view('masters.products.create', compact('categories', 'uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'required|string|max:30|unique:products,product_code',
            'name' => 'required|string|max:150',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'hsn_code' => 'nullable|string|max:20',
            'base_uom_id' => 'required|exists:uoms,id',
            'alternate_uom_id' => 'nullable|exists:uoms,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'batch_tracking' => 'boolean',
            'shelf_life' => 'nullable|integer|min:1',
            'manufacturing_date_required' => 'boolean',
            'expiry_date_required' => 'boolean',
            'storage_instructions' => 'nullable|string',
            'qc_required' => 'boolean',
            'min_stock' => 'nullable|numeric|min:0',
            'is_saleable' => 'boolean',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['batch_tracking'] = $request->boolean('batch_tracking', true);
        $validated['manufacturing_date_required'] = $request->boolean('manufacturing_date_required', true);
        $validated['expiry_date_required'] = $request->boolean('expiry_date_required', true);
        $validated['qc_required'] = $request->boolean('qc_required', true);
        $validated['is_saleable'] = $request->boolean('is_saleable', true);

        $product = Product::create($validated);
        AuditService::log('masters', $product->product_code, 'create', null, $product->toArray(), 'Created product');

        return redirect()->route('masters.products.index')->with('success', "Product '{$product->name}' created successfully.");
    }

    public function show(Product $product)
    {
        $product->load(['category', 'baseUom', 'recipes']);
        return view('masters.products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $categories = ProductCategory::where('status', 'Active')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();
        return view('masters.products.edit', compact('product', 'categories', 'uoms'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'hsn_code' => 'nullable|string|max:20',
            'base_uom_id' => 'required|exists:uoms,id',
            'alternate_uom_id' => 'nullable|exists:uoms,id',
            'conversion_factor' => 'nullable|numeric|min:0.0001',
            'shelf_life' => 'nullable|integer|min:1',
            'storage_instructions' => 'nullable|string',
            'min_stock' => 'nullable|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ]);

        $validated['batch_tracking'] = $request->boolean('batch_tracking');
        $validated['manufacturing_date_required'] = $request->boolean('manufacturing_date_required');
        $validated['expiry_date_required'] = $request->boolean('expiry_date_required');
        $validated['qc_required'] = $request->boolean('qc_required');
        $validated['is_saleable'] = $request->boolean('is_saleable');

        $old = $product->toArray();
        $product->update($validated);
        AuditService::log('masters', $product->product_code, 'update', $old, $product->toArray(), 'Updated product');

        return redirect()->route('masters.products.index')->with('success', "Product '{$product->name}' updated successfully.");
    }

    public function destroy(Product $product)
    {
        if ($product->recipes()->exists() || $product->batches()->exists() || $product->salesOrderItems()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $product->toArray();
            $product->delete();
            AuditService::log('masters', $product->product_code, 'delete', $old, null, 'Deleted product');

            return redirect()->route('masters.products.index')->with('success', "Product deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function toggleStatus(Product $product)
    {
        $old = $product->status;
        $product->status = ($product->status === 'Active') ? 'Inactive' : 'Active';
        $product->save();

        AuditService::log('masters', $product->product_code, 'status_change', ['status' => $old], ['status' => $product->status], 'Toggled product status');

        return redirect()->back()->with('success', "Product '{$product->name}' status updated to {$product->status}.");
    }
}
