<?php

namespace App\Notifications;

use App\Models\Product;
use App\Models\ProductInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Product $product,
        private readonly ProductInventory $inventory
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'Low stock alert: '.$this->product->product_name,
            'product_id' => $this->product->id,
            'stock' => (int) $this->inventory->stock_quantity,
            'threshold' => $this->inventory->low_stock_threshold,
            'unit' => $this->product->unit ?: $this->inventory->unit,
        ];
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
