<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryLog;
use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $this->applyAdjustment($inventory, $data, $request->input('reason'));

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
        $this->applyAdjustment($inventory, $data, $request->input('reason'));

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

    private function applyAdjustment(ProductInventory $inventory, array $data, ?string $reason = null): void
    {
        if ($inventory->inventory_mode === 'epos') {
            throw ValidationException::withMessages(['product_id' => 'Inventory managed via EPOS cannot be manually adjusted.']);
        }

        $previousStock = (int) $inventory->stock_quantity;
        $quantity = (int) $data['quantity'];
        $newStock = $data['adjustment_type'] === 'add' ? $previousStock + $quantity : $previousStock - $quantity;

        if ($newStock < 0) {
            throw ValidationException::withMessages(['quantity' => 'Stock cannot go below zero.']);
        }

        $inventory->update([
            'stock_quantity' => $newStock,
            'last_synced_at' => now(),
        ]);

        InventoryLog::create([
            'product_id' => $inventory->product_id,
            'product_inventory_id' => $inventory->id,
            'change_type' => $data['adjustment_type'],
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'source' => 'internal',
            'reason' => $reason,
        ]);
    }
}
