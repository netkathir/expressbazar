<?php

namespace App\Jobs;

use App\Models\ProductInventory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReverseEposStockJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $productId,
        public int $quantity,
        public ?int $orderId = null
    ) {
    }

    public function handle(): void
    {
        $inventory = ProductInventory::query()
            ->where('product_id', $this->productId)
            ->first();

        if (! $inventory || $inventory->inventory_mode !== 'epos') {
            return;
        }

        try {
            // EPOS integration point: reverse/release stock in the external system.
            $inventory->update([
                'sync_status' => 'reverse_queued',
                'last_synced_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $inventory->update(['sync_status' => 'failed']);
            Log::error('EPOS stock reverse sync failed.', [
                'product_id' => $this->productId,
                'order_id' => $this->orderId,
                'quantity' => $this->quantity,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
