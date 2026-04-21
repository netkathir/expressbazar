<?php

namespace App\Services;

class EposNowService
{
    public function fetchStock(string $sku): array
    {
        return [
            'sku' => $sku,
            'stock_quantity' => rand(20, 250),
            'last_synced_at' => now()->toDateTimeString(),
            'source' => 'eposnow',
        ];
    }

    public function syncProduct(array $product): array
    {
        return [
            'sku' => $product['sku'] ?? null,
            'synced' => true,
            'synced_at' => now()->toDateTimeString(),
        ];
    }
}
