<?php

namespace App\Http\Controllers\Vendor;

use App\Jobs\SyncEposStockJob;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductInventory;
use App\Models\Subcategory;
use App\Models\Tax;
use App\Services\InventoryService;
use App\Services\ProductBulkImportService;
use App\Services\ProductBulkTemplateService;
use App\Support\UploadedImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    private const MAX_MONEY_AMOUNT = 99999999.99;
    private const MONEY_PATTERN = '/^\d{1,8}(\.\d{1,2})?$/';

    public function index(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        $products = Product::query()
            ->with(['category', 'subcategory', 'vendor', 'inventory', 'images'])
            ->where('vendor_id', $vendor->id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('product_name', 'like', "%{$search}%");
            })
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
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
            'vendors' => collect([$vendor]),
            'taxes' => $this->vendorTaxes($vendor),
            'routePrefix' => 'vendor.products',
            'isVendorPanel' => true,
        ]);
    }

    public function create()
    {
        $vendor = Auth::guard('vendor')->user();

        return view('admin.products.form', [
            'title' => 'Add Product',
            'activeMenu' => 'products',
            'product' => new Product(),
            'categories' => Category::orderBy('category_name')->get(),
            'subcategories' => Subcategory::orderBy('subcategory_name')->get(),
            'vendors' => collect([$vendor]),
            'taxes' => $this->vendorTaxes($vendor),
            'mode' => 'create',
            'routePrefix' => 'vendor.products',
            'isVendorPanel' => true,
        ]);
    }

    public function bulkCreate()
    {
        return view('admin.products.bulk', [
            'title' => 'Bulk Import Products',
            'activeMenu' => 'products',
            'routePrefix' => 'vendor.products',
            'isVendorPanel' => true,
            'categories' => Category::query()->with(['subcategories' => fn ($query) => $query->orderBy('subcategory_name')])->orderBy('category_name')->get(['id', 'category_name']),
        ]);
    }

    public function bulkStore(Request $request, ProductBulkImportService $importer)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $importer->import($data['file'], [
            'vendor' => Auth::guard('vendor')->user(),
            'queue_epos_sync' => true,
        ]);

        if ($result['created'] === 0 && ! empty($result['errors'])) {
            return redirect()
                ->route('vendor.products.bulk')
                ->withErrors(['file' => 'No products were imported. Please review the skipped row details below.'])
                ->with('bulk_errors', $result['errors']);
        }

        return redirect()
            ->route('vendor.products.bulk')
            ->with('success', $result['created'].' products imported successfully.'.(empty($result['errors']) ? '' : ' Some rows were skipped.'))
            ->with('bulk_errors', $result['errors']);
    }

    public function bulkTemplate(ProductBulkTemplateService $templateService)
    {
        $path = $templateService->vendorTemplate(Auth::guard('vendor')->user());

        return response()
            ->download($path, 'vendor-product-bulk-template.csv', [
                'Content-Type' => 'text/csv',
            ])
            ->deleteFileAfterSend(true);
    }

    public function store(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();
        $data = $this->validateProduct($request);
        $data['vendor_id'] = $vendor->id;
        $data['inventory_mode'] = $vendor->inventory_mode ?: $data['inventory_mode'];
        $data['status'] = $data['status'] ?: 'active';
        $data['created_by'] = null;
        $data['updated_by'] = null;
        $data['final_price'] = $this->calculateFinalPrice($data);

        $this->validateDiscountDates($data);

        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            $this->syncInventory($product, $request);
            $this->syncImages($product, $request, false);

            return $product;
        });

        $this->queueEposSyncIfNeeded($product);

        return $this->redirectToIndex($request, 'vendor.products.index', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $this->authorizeVendorProduct($product);
        $vendor = Auth::guard('vendor')->user();

        return view('admin.products.form', [
            'title' => 'Edit Product',
            'activeMenu' => 'products',
            'product' => $product->loadMissing(['images', 'inventory']),
            'categories' => Category::orderBy('category_name')->get(),
            'subcategories' => Subcategory::orderBy('subcategory_name')->get(),
            'vendors' => collect([$vendor]),
            'taxes' => $this->vendorTaxes($vendor),
            'mode' => 'edit',
            'routePrefix' => 'vendor.products',
            'isVendorPanel' => true,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeVendorProduct($product);

        $data = $this->validateProduct($request, $product);
        $vendor = Auth::guard('vendor')->user();
        $data['vendor_id'] = $vendor->id;
        $data['inventory_mode'] = $vendor->inventory_mode ?: $data['inventory_mode'];
        $data['updated_by'] = null;
        $data['final_price'] = $this->calculateFinalPrice($data);

        $this->validateDiscountDates($data);

        DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            $this->syncInventory($product, $request);
            $this->removeSelectedImages($product, $request);
            $this->syncImages($product, $request, true);
        });

        $this->queueEposSyncIfNeeded($product);

        return $this->redirectToIndex($request, 'vendor.products.index', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product)
    {
        $this->authorizeVendorProduct($product);
        $this->deleteImages($product);
        $this->deleteFromDatabase($product);

        return $this->redirectToIndex($request, 'vendor.products.index', 'Product deleted successfully.');
    }

    public function destroyImage(ProductImage $image)
    {
        $product = $image->product;
        abort_if(! $product || (int) $product->vendor_id !== (int) Auth::guard('vendor')->id(), 404);

        UploadedImage::delete($image->image_path);

        $productId = $image->product_id;
        $image->delete();

        return redirect()
            ->route('vendor.products.edit', $productId)
            ->with('success', 'Product image deleted successfully.');
    }

    private function authorizeVendorProduct(Product $product): void
    {
        abort_if((int) $product->vendor_id !== (int) Auth::guard('vendor')->id(), 404);
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $vendorId = Auth::guard('vendor')->id();
        $request->merge([
            'product_name' => trim((string) $request->input('product_name')),
        ]);

        $data = $request->validate([
            'product_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'product_name')
                    ->where('vendor_id', $vendorId)
                    ->ignore($product?->id),
            ],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => [
                'nullable',
                Rule::exists('subcategories', 'id')
                    ->where(fn ($query) => $query->where('category_id', $request->integer('category_id'))),
            ],
            'tax_id' => [
                'nullable',
                Rule::exists('taxes', 'id')
                    ->where('country_id', Auth::guard('vendor')->user()?->country_id ?: 0)
                    ->where('status', 'active'),
            ],
            'price' => ['required', 'numeric', 'min:0.01', 'max:'.self::MAX_MONEY_AMOUNT, 'regex:'.self::MONEY_PATTERN],
            'discount_type' => ['nullable', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['nullable', 'required_with:discount_type', 'numeric', 'min:0.01', 'max:'.self::MAX_MONEY_AMOUNT, 'regex:'.self::MONEY_PATTERN],
            'discount_start_date' => ['nullable', 'required_with:discount_end_date', 'date'],
            'discount_end_date' => ['nullable', 'required_with:discount_start_date', 'date', 'after_or_equal:discount_start_date'],
            'inventory_mode' => ['required', Rule::in(['internal', 'epos'])],
            'stock_quantity' => ['required_if:inventory_mode,internal', 'integer', 'min:0'],
            'unit' => ['required', Rule::in(['kg', 'nos', 'pieces'])],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'images' => [$product ? 'nullable' : 'required', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'],
            'remove_image_ids' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'price.regex' => 'Price must be 99,999,999.99 or less with up to two decimal places.',
            'price.min' => 'Price must be greater than zero.',
            'price.max' => 'Price cannot be more than 99,999,999.99.',
            'discount_value.required_with' => 'Discount value is required when a discount type is selected.',
            'discount_value.min' => 'Discount value must be greater than zero.',
            'discount_value.regex' => 'Discount value must be 99,999,999.99 or less with up to two decimal places.',
            'discount_value.max' => 'Discount value cannot be more than 99,999,999.99.',
            'discount_start_date.required_with' => 'Discount start date is required when discount end date is selected.',
            'discount_end_date.required_with' => 'Discount end date is required when discount start date is selected.',
            'discount_end_date.after_or_equal' => 'Discount end date must be after or equal to the start date.',
            'stock_quantity.required_if' => 'Stock quantity is required for internal inventory products.',
            'unit.required' => 'Unit is required.',
            'images.required' => 'At least one product image is required.',
            'images.*.max' => 'Each product image must be 2 MB or smaller.',
            'images.*.mimes' => 'Product images must be JPG, JPEG, PNG, WEBP or GIF files.',
        ]);

        if (isset($data['low_stock_threshold'], $data['stock_quantity']) && $data['low_stock_threshold'] > $data['stock_quantity']) {
            throw ValidationException::withMessages(['low_stock_threshold' => 'Low stock threshold cannot be greater than stock quantity.']);
        }

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

    private function vendorTaxes($vendor)
    {
        if (! $vendor?->country_id) {
            return collect();
        }

        return Tax::query()
            ->where('country_id', $vendor->country_id)
            ->where('status', 'active')
            ->orderBy('tax_name')
            ->get();
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

    private function validateDiscountDates(array $data): void
    {
        if (($data['discount_start_date'] ?? null) && ($data['discount_end_date'] ?? null) && $data['discount_start_date'] > $data['discount_end_date']) {
            throw ValidationException::withMessages(['discount_end_date' => 'Discount end date must be after the start date.']);
        }
    }

    private function syncInventory(Product $product, Request $request): void
    {
        $inventoryMode = $product->inventory_mode;
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

    private function queueEposSyncIfNeeded(Product $product): void
    {
        if ($product->inventory_mode !== 'epos') {
            return;
        }

        $product->inventory?->update(['sync_status' => 'pending']);
        SyncEposStockJob::dispatch($product->id, 0);
    }

    private function syncImages(Product $product, Request $request, bool $replaceExisting = false): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        if ($replaceExisting) {
            foreach ($product->images as $existingImage) {
                UploadedImage::delete($existingImage->image_path);
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
                'created_by' => null,
                'updated_by' => null,
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
                UploadedImage::delete($image->image_path);

                $image->delete();
            });
    }

    private function deleteImages(Product $product): void
    {
        foreach ($product->images as $image) {
            UploadedImage::delete($image->image_path);
        }
    }

    private function storeImage($file): string
    {
        return UploadedImage::store($file, 'products', 'product');
    }
}
