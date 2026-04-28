<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $inventories = ProductInventory::query()
            ->with(['product.vendor'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->whereHas('product', fn ($sub) => $sub->where('product_name', 'like', "%{$search}%"));
            })
            ->when($request->filled('inventory_mode'), fn ($query) => $query->where('inventory_mode', $request->string('inventory_mode')))
            ->when($request->boolean('low_stock'), function ($query) {
                $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.inventory.index', [
            'title' => 'Inventory Management',
            'activeMenu' => 'inventory',
            'inventories' => $inventories,
            'products' => Product::orderBy('product_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.inventory.form', [
            'title' => 'Adjust Stock',
            'activeMenu' => 'inventory',
            'inventory' => new ProductInventory(),
            'products' => Product::with('inventory')->orderBy('product_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateInventory($request);
        $product = Product::findOrFail($data['product_id']);
        $inventory = ProductInventory::firstOrCreate(
            ['product_id' => $product->id],
            ['inventory_mode' => $product->inventory_mode, 'stock_quantity' => 0]
        );

        app(InventoryService::class)->adjustStock($inventory, (int) $data['quantity'], $data['adjustment_type'], $request->input('reason'));

        return redirect()->route('admin.inventory.index')->with('success', 'Inventory updated successfully.');
    }

    public function edit(ProductInventory $inventory)
    {
        return view('admin.inventory.form', [
            'title' => 'Edit Stock',
            'activeMenu' => 'inventory',
            'inventory' => $inventory->load('product'),
            'products' => Product::with('inventory')->orderBy('product_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, ProductInventory $inventory)
    {
        $data = $this->validateInventory($request, $inventory->product_id);
        app(InventoryService::class)->adjustStock($inventory, (int) $data['quantity'], $data['adjustment_type'], $request->input('reason'));

        return redirect()->route('admin.inventory.index')->with('success', 'Inventory updated successfully.');
    }

    public function destroy(ProductInventory $inventory)
    {
        $inventory->delete();

        return redirect()->route('admin.inventory.index')->with('success', 'Inventory record removed.');
    }

    private function validateInventory(Request $request, ?int $ignoreProductId = null): array
    {
        return $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'adjustment_type' => ['required', Rule::in(['add', 'reduce'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
    }

}
