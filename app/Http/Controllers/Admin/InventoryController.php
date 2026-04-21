<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(): View
    {
        $items = VendorProduct::query()
            ->with(['vendor', 'product.subcategory.category'])
            ->latest()
            ->paginate(10);

        return view('admin.catalog.inventory.index', [
            'pageTitle' => 'Inventory | Admin | ExpressBazar',
            'items' => $items,
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.inventory.create', [
            'pageTitle' => 'Create Inventory Item | Admin | ExpressBazar',
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'products' => Product::query()->with('subcategory')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $data['is_active'] = $request->boolean('is_active');

        VendorProduct::updateOrCreate(
            ['vendor_id' => $data['vendor_id'], 'product_id' => $data['product_id']],
            [
                'price' => $data['price'],
                'stock' => $data['stock'],
                'is_active' => $data['is_active'],
            ]
        );

        return redirect()->route('admin.inventory')->with('status', 'Inventory saved successfully.');
    }

    public function edit(VendorProduct $inventory): View
    {
        return view('admin.catalog.inventory.edit', [
            'pageTitle' => 'Edit Inventory Item | Admin | ExpressBazar',
            'inventory' => $inventory->load(['vendor', 'product']),
            'vendors' => Vendor::query()->orderBy('name')->get(),
            'products' => Product::query()->with('subcategory')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, VendorProduct $inventory): RedirectResponse
    {
        $data = $this->validatedData($request, $inventory->id);
        $data['is_active'] = $request->boolean('is_active');

        $inventory->update([
            'vendor_id' => $data['vendor_id'],
            'product_id' => $data['product_id'],
            'price' => $data['price'],
            'stock' => $data['stock'],
            'is_active' => $data['is_active'],
        ]);

        return redirect()->route('admin.inventory')->with('status', 'Inventory updated successfully.');
    }

    public function destroy(VendorProduct $inventory): RedirectResponse
    {
        $inventory->delete();

        return redirect()->route('admin.inventory')->with('status', 'Inventory item deleted successfully.');
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'vendor_id' => ['required', 'exists:vendors,id'],
            'product_id' => ['required', 'exists:products,id'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($ignoreId) {
            $rules['product_id'][] = Rule::unique('vendor_products')
                ->where(fn ($query) => $query->where('vendor_id', $request->integer('vendor_id')))
                ->ignore($ignoreId);
        }

        return $request->validate($rules);
    }
}
