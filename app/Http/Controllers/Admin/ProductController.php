<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::query()
            ->with(['vendor', 'subcategory.category'])
            ->withSum('vendorProducts as inventory_stock', 'stock')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.catalog.products.index', [
            'pageTitle' => 'Products | Admin | ExpressBazar',
            'products' => $products,
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.products.create', [
            'pageTitle' => 'Create Product | Admin | ExpressBazar',
            'subcategories' => Subcategory::query()->with('category')->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');
        $data['stock_quantity'] = 0;

        Product::create($data);

        return redirect()->route('admin.products')->with('status', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        return view('admin.catalog.products.edit', [
            'pageTitle' => 'Edit Product | Admin | ExpressBazar',
            'product' => $product->load(['subcategory.category', 'vendor']),
            'subcategories' => Subcategory::query()->with('category')->orderBy('name')->get(),
            'vendors' => Vendor::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validatedData($request, $product->id);
        $data['is_active'] = $request->boolean('is_active');
        $data['stock_quantity'] = 0;

        $product->update($data);

        return redirect()->route('admin.products')->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products')->with('status', 'Product deleted successfully.');
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'vendor_id' => ['required', 'exists:vendors,id'],
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug' . ($ignoreId ? ',' . $ignoreId : '')],
            'description' => ['nullable', 'string'],
            'sku' => ['required', 'string', 'max:255', 'unique:products,sku' . ($ignoreId ? ',' . $ignoreId : '')],
            'price' => ['required', 'integer', 'min:0'],
            'mrp' => ['required', 'integer', 'min:0'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'unit' => ['nullable', 'string', 'max:100'],
            'deal_text' => ['nullable', 'string', 'max:50'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'background_color' => ['nullable', 'string', 'max:20'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
