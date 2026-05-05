<?php

namespace App\Support;

use App\Models\Category;
use App\Models\City;
use App\Models\Coupon;
use App\Models\Country;
use App\Models\Product;
use App\Models\RegionZone;

class StorefrontLayoutData
{
    public function defaults(array $existing = []): array
    {
        $location = $existing['location'] ?? $this->browsingLocation();
        $cartItems = $existing['cartItems'] ?? $this->cartItems();

        return [
            'location' => $location,
            'locationLabel' => $existing['locationLabel'] ?? $this->locationLabel($location),
            'cartCount' => $existing['cartCount'] ?? $this->cartCount(),
            'cartItems' => $cartItems,
            'cartMap' => $existing['cartMap'] ?? $cartItems->keyBy(fn ($item) => $item['product']->id),
            'cartState' => $existing['cartState'] ?? $this->cartState(),
            'cartTotals' => $existing['cartTotals'] ?? $this->cartTotals($cartItems),
            'categories' => $existing['categories'] ?? $this->activeCategories(),
            'countries' => $existing['countries'] ?? $this->activeCountries(),
        ];
    }

    private function browsingLocation(): ?array
    {
        $hardLocation = session('storefront.location');

        if (is_array($hardLocation)) {
            return $hardLocation;
        }

        $softLocation = session('storefront.soft_location');

        return is_array($softLocation) ? $softLocation : null;
    }

    private function locationLabel(?array $location): string
    {
        if (! $location) {
            return 'Select Location';
        }

        $city = ! empty($location['city_id']) ? City::find($location['city_id']) : null;
        $zone = ! empty($location['zone_id']) ? RegionZone::find($location['zone_id']) : null;

        if ($zone) {
            return collect([$city?->city_name, $zone->zone_name])
                ->filter()
                ->implode(html_entity_decode(' &middot; ', ENT_QUOTES, 'UTF-8'));
        }

        return $city?->city_name ?? 'Select Location';
    }

    private function cartItems()
    {
        $cart = session()->get('storefront.cart', []);

        if (empty($cart)) {
            return collect();
        }

        $products = Product::query()
            ->with(['images', 'vendor', 'inventory'])
            ->whereIn('id', array_keys($cart))
            ->get()
            ->keyBy('id');

        return collect($cart)->map(function ($item, $productId) use ($products) {
            $product = $products->get($productId);

            if (! $product) {
                return null;
            }

            $quantity = (int) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($product->final_price ?: $product->price);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $unitPrice * $quantity,
            ];
        })->filter()->values();
    }

    private function cartState(): array
    {
        return collect(session()->get('storefront.cart', []))
            ->map(function (array $item, int|string $productId) {
                return [
                    'product_id' => (int) $productId,
                    'quantity' => (int) ($item['quantity'] ?? 0),
                ];
            })
            ->filter(fn ($item) => $item['product_id'] > 0 && $item['quantity'] > 0)
            ->values()
            ->all();
    }

    private function cartCount(): int
    {
        return collect(session()->get('storefront.cart', []))->sum('quantity');
    }

    private function cartTotals($items): array
    {
        $itemTotal = $items->sum('subtotal');
        $coupon = $this->validCouponForCart((float) $itemTotal);
        $discount = $coupon ? $this->couponDiscount($coupon, (float) $itemTotal) : 0.0;

        return [
            'itemTotal' => $itemTotal,
            'delivery' => 0,
            'discount' => $discount,
            'grandTotal' => max(0, $itemTotal - $discount),
            'coupon' => $coupon ? [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
            ] : null,
        ];
    }

    private function validCouponForCart(float $itemTotal): ?Coupon
    {
        $sessionCoupon = session('storefront.coupon');

        if (! is_array($sessionCoupon) || empty($sessionCoupon['id'])) {
            return null;
        }

        $coupon = Coupon::query()
            ->whereKey($sessionCoupon['id'])
            ->where('is_active', true)
            ->first();

        if (! $coupon || ($coupon->expires_at && now()->gt($coupon->expires_at))) {
            session()->forget('storefront.coupon');
            return null;
        }

        if ($coupon->min_order !== null && $itemTotal < (float) $coupon->min_order) {
            session()->forget('storefront.coupon');
            return null;
        }

        $vendorId = session('storefront.cart_vendor_id');
        if ($coupon->vendor_id && (! $vendorId || (int) $coupon->vendor_id !== (int) $vendorId)) {
            session()->forget('storefront.coupon');
            return null;
        }

        return $coupon;
    }

    private function couponDiscount(Coupon $coupon, float $itemTotal): float
    {
        $discount = $coupon->type === 'fixed'
            ? (float) $coupon->value
            : $itemTotal * ((float) $coupon->value / 100);

        return min($itemTotal, max(0, round($discount, 2)));
    }

    private function activeCategories()
    {
        return Category::query()
            ->where('status', 'active')
            ->withCount('products')
            ->orderBy('category_name')
            ->get();
    }

    private function activeCountries()
    {
        return Country::query()
            ->where('status', 'active')
            ->orderBy('country_name')
            ->get();
    }
}
