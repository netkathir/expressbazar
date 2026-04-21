<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'marketplace_cart';
    private const COUPON_KEY = 'marketplace_coupon_code';

    public function items(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function add(array $product, int $quantity = 1): array
    {
        $items = $this->items();
        $key = (string) $product['id'];
        $currentVendorId = $this->vendorId();

        if ($currentVendorId !== null && (int) $currentVendorId !== (int) $product['vendor_id']) {
            $items = [];
            Session::forget(self::COUPON_KEY);
        }

        if (! isset($items[$key])) {
            $items[$key] = [
                'vendor_product_id' => $product['vendor_product_id'] ?? null,
                'product_id' => $product['product_id'] ?? $product['id'],
                'name' => $product['name'],
                'slug' => $product['slug'],
                'vendor_id' => $product['vendor_id'],
                'vendor_name' => $product['vendor_name'] ?? null,
                'quantity' => 0,
                'unit_price' => $product['price'],
                'image' => $product['image'] ?? null,
            ];
        }

        $items[$key]['quantity'] += max(1, $quantity);
        Session::put(self::SESSION_KEY, $items);

        return $items;
    }

    public function remove(int $productId): array
    {
        $items = $this->items();
        unset($items[(string) $productId]);
        Session::put(self::SESSION_KEY, $items);

        return $items;
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::COUPON_KEY);
    }

    public function lines(): array
    {
        return collect($this->items())->values()->all();
    }

    public function vendorId(): ?int
    {
        return collect($this->items())->pluck('vendor_id')->unique()->values()->first();
    }

    public function couponCode(): ?string
    {
        $code = Session::get(self::COUPON_KEY);

        return is_string($code) && $code !== '' ? $code : null;
    }

    public function applyCoupon(string $code): void
    {
        Session::put(self::COUPON_KEY, strtoupper(trim($code)));
    }

    public function removeCoupon(): void
    {
        Session::forget(self::COUPON_KEY);
    }
}
