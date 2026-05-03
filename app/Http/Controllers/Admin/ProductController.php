<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\Tax;
use App\Models\Subcategory;
use App\Models\Vendor;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->with(['category', 'subcategory', 'vendor', 'inventory', 'images'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('product_name', 'like', "%{$search}%");
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->filled('vendor_id'), fn ($query) => $query->where('vendor_id', $request->integer('vendor_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('inventory_mode'), fn ($query) => $query->where('inventory_mode', $request->string('inventory_mode')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.products.index', [
            'title' => 'Product Management',
            'activeMenu' => 'products',
            'products' => $products,
            'categories' => Category::orderBy('category_name')->get(),
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'taxes' => Tax::orderBy('tax_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'title' => 'Add Product',
            'activeMenu' => 'products',
            'product' => new Product(),
            'categories' => Category::orderBy('category_name')->get(),
            'subcategories' => Subcategory::orderBy('subcategory_name')->get(),
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'taxes' => Tax::orderBy('tax_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;
        $data['final_price'] = $this->calculateFinalPrice($data);

        if ($data['discount_type'] && ! isset($data['discount_value'])) {
            $data['discount_value'] = 0;
        }

        if (($data['discount_start_date'] ?? null) && ($data['discount_end_date'] ?? null) && $data['discount_start_date'] > $data['discount_end_date']) {
            throw ValidationException::withMessages(['discount_end_date' => 'Discount end date must be after the start date.']);
        }

        $product = Product::create($data);
        $this->syncInventory($product, $request);
        $this->syncImages($product, $request, false);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'title' => 'Edit Product',
            'activeMenu' => 'products',
            'product' => $product,
            'categories' => Category::orderBy('category_name')->get(),
            'subcategories' => Subcategory::orderBy('subcategory_name')->get(),
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'taxes' => Tax::orderBy('tax_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product);
        $data['updated_by'] = $request->user()?->id;
        $data['final_price'] = $this->calculateFinalPrice($data);

        if (($data['discount_start_date'] ?? null) && ($data['discount_end_date'] ?? null) && $data['discount_start_date'] > $data['discount_end_date']) {
            throw ValidationException::withMessages(['discount_end_date' => 'Discount end date must be after the start date.']);
        }

        $product->update($data);
        $this->syncInventory($product, $request);
        $this->removeSelectedImages($product, $request);
        $this->syncImages($product, $request, true);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $this->deleteImages($product);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }

    public function destroyImage(ProductImage $image)
    {
        $fullPath = public_path($image->image_path);

        if (File::exists($fullPath)) {
            File::delete($fullPath);
        }

        $productId = $image->product_id;
        $image->delete();

        return redirect()
            ->route('admin.products.edit', $productId)
            ->with('success', 'Product image deleted successfully.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'product_name' => ['required', 'string', 'max:255', Rule::unique('products', 'product_name')->ignore($product?->id)],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['nullable', 'exists:subcategories,id'],
            'vendor_id' => ['required', 'exists:vendors,id'],
            'tax_id' => ['nullable', 'exists:taxes,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'discount_start_date' => ['nullable', 'date'],
            'discount_end_date' => ['nullable', 'date'],
            'inventory_mode' => ['required', Rule::in(['internal', 'epos'])],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'unit' => ['nullable', Rule::in(['kg', 'nos', 'pieces'])],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:2048'],
            'remove_image_ids' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        if (empty($data['discount_type']) || empty($data['discount_value'])) {
            $data['discount_type'] = null;
            $data['discount_value'] = null;
            $data['discount_start_date'] = null;
            $data['discount_end_date'] = null;

            return $data;
        }

        if (! empty($data['discount_value'])) {
            if (($data['discount_type'] ?? null) === 'percentage' && $data['discount_value'] > 100) {
                throw ValidationException::withMessages(['discount_value' => 'Percentage discount cannot exceed 100.']);
            }

            if (($data['discount_type'] ?? null) === 'fixed' && $data['discount_value'] > $data['price']) {
                throw ValidationException::withMessages(['discount_value' => 'Fixed discount cannot exceed the product price.']);
            }
        }

        return $data;
    }

    private function calculateFinalPrice(array $data): string
    {
        $price = (float) $data['price'];
        $discountType = $data['discount_type'] ?? null;
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discount = 0;

        if ($discountType === 'percentage') {
            $discount = $price * ($discountValue / 100);
        } elseif ($discountType === 'fixed') {
            $discount = $discountValue;
        }

        return number_format(max(0, $price - $discount), 2, '.', '');
    }

    private function syncInventory(Product $product, Request $request): void
    {
        $inventoryMode = $request->string('inventory_mode')->toString();
        $stockQuantity = $inventoryMode === 'internal' ? (int) ($request->input('stock_quantity', 0) ?: 0) : 0;

        ProductInventory::updateOrCreate(
            ['product_id' => $product->id],
            [
                'inventory_mode' => $inventoryMode,
                'stock_quantity' => $stockQuantity,
                'unit' => $request->input('unit'),
                'low_stock_threshold' => $request->input('low_stock_threshold'),
                'sync_status' => $inventoryMode === 'epos' ? 'managed via EPOS' : 'internal',
                'last_synced_at' => now(),
            ]
        );

        $product->forceFill(['unit' => $request->input('unit')])->save();
        app(InventoryService::class)->notifyIfLowStock($product->inventory()->first());
    }

    private function syncImages(Product $product, Request $request, bool $replaceExisting = false): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        if ($replaceExisting) {
            foreach ($product->images as $existingImage) {
                $fullPath = public_path($existingImage->image_path);

                if (File::exists($fullPath)) {
                    File::delete($fullPath);
                }
            }

            $product->images()->delete();
        }

        foreach ($request->file('images') as $index => $file) {
            if (! $file->isValid()) {
                continue;
            }

            $path = $this->storeImage($file);

            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'sort_order' => $index,
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ]);
        }
    }

    private function removeSelectedImages(Product $product, Request $request): void
    {
        $imageIds = collect(explode(',', (string) $request->input('remove_image_ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->unique()
            ->values();

        if ($imageIds->isEmpty()) {
            return;
        }

        $product->images()
            ->whereIn('id', $imageIds)
            ->get()
            ->each(function (ProductImage $image) {
                $fullPath = public_path($image->image_path);

                if (File::exists($fullPath)) {
                    File::delete($fullPath);
                }

                $image->delete();
            });
    }

    private function deleteImages(Product $product): void
    {
        foreach ($product->images as $image) {
            $fullPath = public_path($image->image_path);

            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }

    private function storeImage($file): string
    {
        $directory = public_path('uploads/products');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = uniqid('product_', true).'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'uploads/products/'.$filename;
    }
}
