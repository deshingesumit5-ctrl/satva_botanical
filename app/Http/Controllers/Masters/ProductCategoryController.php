<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ProductCategory::withCount('products');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = (int) $request->input('per_page', 10);
        $categories = $query->orderBy('display_order')->paginate($perPage)->withQueryString();

        return view('masters.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30|unique:product_categories,code',
            'display_order' => 'required|integer|min:1',
            'thumbnail' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = 'CAT-' . strtoupper(Str::slug(substr($validated['name'], 0, 10))) . '-' . rand(100, 999);
        }

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('categories', 'public');
        }

        $category = ProductCategory::create($validated);
        AuditService::log('masters', $category->code, 'create', null, $category->toArray(), 'Created category');

        return redirect()->route('masters.categories.index')->with('success', "Category '{$category->name}' created successfully.");
    }

    public function edit(ProductCategory $category)
    {
        if (request()->wantsJson()) {
            return response()->json($category);
        }
        return view('masters.categories.edit', compact('category'));
    }

    public function update(Request $request, ProductCategory $category)
    {
        if (!$request->filled('display_order')) {
            $request->merge(['display_order' => $category->display_order ?? 1]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'display_order' => 'required|integer|min:1',
            'thumbnail' => 'nullable|image|max:2048',
            'status' => 'required|in:Active,Inactive',
        ]);

        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail'] = $request->file('thumbnail')->store('categories', 'public');
        }

        $old = $category->toArray();
        $category->update($validated);
        AuditService::log('masters', $category->code, 'update', $old, $category->toArray(), 'Updated category');

        return redirect()->route('masters.categories.index')->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function destroy(ProductCategory $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }

        try {
            $old = $category->toArray();
            $category->delete();
            AuditService::log('masters', $category->code, 'delete', $old, null, 'Deleted category');

            return redirect()->route('masters.categories.index')->with('success', "Category deleted successfully.");
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', "This record is already used and cannot be deleted. You can deactivate it.");
        }
    }

    public function toggleStatus(ProductCategory $category)
    {
        $old = $category->status;
        $category->status = ($category->status === 'Active') ? 'Inactive' : 'Active';
        $category->save();

        AuditService::log('masters', $category->code, 'status_change', ['status' => $old], ['status' => $category->status], 'Toggled category status');

        return redirect()->back()->with('success', "Category '{$category->name}' status updated to {$category->status}.");
    }

    public function create()
    {
        return redirect()->back();
    }

    public function show($id = null)
    {
        return redirect()->back();
    }
}
