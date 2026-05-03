<?php

namespace App\Services;

use App\Jobs\ReverseEposStockJob;
use App\Jobs\SyncEposStockJob;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\ProductInventory;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class InventoryService
{
    public function adjustStock(ProductInventory $inventory, int $quantity, string $type, ?string $reason = null): ProductInventory
    {
        if ($inventory->inventory_mode === 'epos') {
            throw ValidationException::withMessages(['product_id' => 'Inventory managed via EPOS cannot be manually adjusted.']);
        }

        $previousStock = null;

        $updatedInventory = DB::transaction(function () use ($inventory, $quantity, $type, $reason, &$previousStock) {
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

        $this->notifyIfLowStock($updatedInventory, $previousStock);

        return $updatedInventory;
    }

    public function syncFromEpos(ProductInventory $inventory, int $newStock, ?string $reason = null): ProductInventory
    {
        $previousStock = null;

        $updatedInventory = DB::transaction(function () use ($inventory, $newStock, $reason, &$previousStock) {
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

        $this->notifyIfLowStock($updatedInventory, $previousStock);

        return $updatedInventory;
    }

    public function notifyIfLowStock(?ProductInventory $inventory, ?int $previousStock = null): void
    {
        if (! $inventory) {
            return;
        }

        $inventory->loadMissing('product');

        if (! $inventory->product || ! Schema::hasColumn('products', 'is_low_stock')) {
            return;
        }

        $product = $inventory->product;
        $currentStock = (int) $inventory->stock_quantity;
        $threshold = is_null($inventory->low_stock_threshold) ? 0 : (int) $inventory->low_stock_threshold;

        if ($currentStock > $threshold) {
            $this->resolveLowStockAlert($inventory);

            return;
        }

        if ($product->is_low_stock) {
            return;
        }

        try {
            if (Schema::hasTable('notifications')) {
                User::query()
                    ->where('status', 'active')
                    ->whereNotNull('role')
                    ->where('role', '!=', 'customer')
                    ->get()
                    ->each(fn (User $admin) => $admin->notify(new LowStockNotification($product, $inventory)));

                Vendor::query()
                    ->whereKey($product->vendor_id)
                    ->where('status', 'active')
                    ->get()
                    ->each(fn (Vendor $vendor) => $vendor->notify(new LowStockNotification($product, $inventory)));
            }

            $product->forceFill(['is_low_stock' => true])->save();
        } catch (Throwable $exception) {
            Log::error('Low stock database notification failed.', [
                'product_id' => $inventory->product_id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveLowStockAlert(ProductInventory $inventory): void
    {
        $inventory->loadMissing('product');
        $product = $inventory->product;

        if (! $product || ! $product->is_low_stock) {
            return;
        }

        try {
            if (Schema::hasTable('notifications')) {
                DB::table('notifications')
                    ->where('type', LowStockNotification::class)
                    ->where('data->product_id', $product->id)
                    ->whereNull('read_at')
                    ->update([
                        'read_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $product->forceFill(['is_low_stock' => false])->save();
        } catch (Throwable $exception) {
            Log::error('Low stock alert cleanup failed.', [
                'product_id' => $inventory->product_id,
                'error' => $exception->getMessage(),
            ]);
        }
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
