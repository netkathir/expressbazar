<?php

namespace App\Services;

use App\Jobs\ReverseEposStockJob;
use App\Jobs\SyncEposStockJob;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjustStock(ProductInventory $inventory, int $quantity, string $type, ?string $reason = null): ProductInventory
    {
        if ($inventory->inventory_mode === 'epos') {
            throw ValidationException::withMessages(['product_id' => 'Inventory managed via EPOS cannot be manually adjusted.']);
        }

        return DB::transaction(function () use ($inventory, $quantity, $type, $reason) {
            $lockedInventory = ProductInventory::query()
                ->whereKey($inventory->id)
                ->lockForUpdate()
                ->with('product')
                ->firstOrFail();

            $previousStock = (int) $lockedInventory->stock_quantity;
            $newStock = $type === 'add'
                ? $previousStock + $quantity
                : $previousStock - $quantity;

            if ($newStock < 0) {
                throw ValidationException::withMessages(['quantity' => 'Stock cannot go below zero.']);
            }

            $lockedInventory->update([
                'stock_quantity' => $newStock,
                'last_synced_at' => now(),
            ]);

            $this->log($lockedInventory, $type, $quantity, $previousStock, $newStock, 'internal', $reason);

            return $lockedInventory;
        });
    }

    public function syncFromEpos(ProductInventory $inventory, int $newStock, ?string $reason = null): ProductInventory
    {
        return DB::transaction(function () use ($inventory, $newStock, $reason) {
            $lockedInventory = ProductInventory::query()
                ->whereKey($inventory->id)
                ->lockForUpdate()
                ->with('product')
                ->firstOrFail();

            $previousStock = (int) $lockedInventory->stock_quantity;

            $lockedInventory->update([
                'stock_quantity' => max(0, $newStock),
                'sync_status' => 'synced',
                'last_synced_at' => now(),
            ]);

            $this->log(
                $lockedInventory,
                'sync',
                abs(max(0, $newStock) - $previousStock),
                $previousStock,
                max(0, $newStock),
                'epos',
                $reason
            );

            return $lockedInventory;
        });
    }

    public function deductForOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = ProductInventory::query()
                ->where('product_id', $item->product_id)
                ->with('product')
                ->first();

            if (! $inventory) {
                continue;
            }

            if ($inventory->inventory_mode === 'epos') {
                $inventory->update(['sync_status' => 'pending']);
                SyncEposStockJob::dispatch($item->product_id, (int) $item->quantity, $order->id);
                continue;
            }

            $this->adjustStock($inventory, (int) $item->quantity, 'reduce', 'Order placed '.$order->order_number);
        }
    }

    public function restoreForCancelledOrder(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = ProductInventory::query()
                ->where('product_id', $item->product_id)
                ->with('product')
                ->first();

            if (! $inventory) {
                continue;
            }

            if ($inventory->inventory_mode === 'epos') {
                $inventory->update(['sync_status' => 'pending']);
                ReverseEposStockJob::dispatch($item->product_id, (int) $item->quantity, $order->id);
                continue;
            }

            $this->adjustStock($inventory, (int) $item->quantity, 'add', 'Order cancelled '.$order->order_number);
        }
    }

    private function log(ProductInventory $inventory, string $type, int $quantity, int $previousStock, int $newStock, string $source, ?string $reason = null): void
    {
        InventoryLog::create([
            'product_id' => $inventory->product_id,
            'product_inventory_id' => $inventory->id,
            'vendor_id' => $inventory->product?->vendor_id,
            'change_type' => $type,
            'quantity' => $quantity,
            'previous_stock' => $previousStock,
            'new_stock' => $newStock,
            'source' => $source,
            'reason' => $reason,
        ]);
    }
}
