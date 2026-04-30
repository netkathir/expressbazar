<?php

namespace App\Services;

use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorOrderWorkflowService
{
    public function accept(Order $order): void
    {
        $this->transition($order, 'accepted', 'Vendor accepted order.');
    }

    public function reject(Order $order, ?string $reason = null): void
    {
        $note = trim((string) $reason) ?: 'Vendor rejected order.';
        $this->transition($order, 'cancelled', $note);
    }

    public function markProcessing(Order $order): void
    {
        $this->transition($order, 'processing', 'Vendor marked order processing.');
    }

    public function markDispatched(Order $order): void
    {
        $this->transition($order, 'dispatched', 'Vendor marked order dispatched.');
    }

    public function markDelivered(Order $order): void
    {
        $this->transition($order, 'delivered', 'Vendor marked order delivered.');
    }

    public function autoCancelPending(int $minutes = 30): int
    {
        $orders = Order::query()
            ->with('items')
            ->where('order_status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            try {
                $this->transition($order, 'cancelled', 'Auto cancelled after '.$minutes.' minutes pending.');
                $count++;
            } catch (\Throwable) {
                continue;
            }
        }

        return $count;
    }

    private function transition(Order $order, string $status, string $note): void
    {
        DB::transaction(function () use ($order, $status, $note) {
            $lockedOrder = Order::query()
                ->with(['items.product.inventory'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($status === 'accepted') {
                $this->ensureStockAvailableForAcceptance($lockedOrder);
            }

            app(OrderLifecycleService::class)->transition($lockedOrder, $status, null, $note);

            if ($status === 'cancelled') {
                app(InventoryService::class)->restoreForCancelledOrder($lockedOrder);
            }
        });
    }

    private function ensureStockAvailableForAcceptance(Order $order): void
    {
        foreach ($order->items as $item) {
            $inventory = ProductInventory::query()
                ->where('product_id', $item->product_id)
                ->with('product')
                ->first();

            if (! $inventory) {
                throw ValidationException::withMessages([
                    'order_status' => "Inventory is missing for {$item->item_name}.",
                ]);
            }

            if ($inventory->inventory_mode === 'epos') {
                if ($inventory->sync_status === 'failed') {
                    throw ValidationException::withMessages([
                        'order_status' => "EPOS inventory sync failed for {$item->item_name}.",
                    ]);
                }

                continue;
            }

            if ($this->hasCheckoutReservation($order, $inventory)) {
                if ((int) $inventory->stock_quantity < 0) {
                    throw ValidationException::withMessages([
                        'order_status' => "Insufficient stock for {$item->item_name}.",
                    ]);
                }

                continue;
            }

            if ((int) $inventory->stock_quantity < (int) $item->quantity) {
                throw ValidationException::withMessages([
                    'order_status' => "Insufficient stock for {$item->item_name}.",
                ]);
            }
        }
    }

    private function hasCheckoutReservation(Order $order, ProductInventory $inventory): bool
    {
        return InventoryLog::query()
            ->where('product_inventory_id', $inventory->id)
            ->where('change_type', 'reduce')
            ->where('reason', 'Order placed '.$order->order_number)
            ->exists();
    }
}
