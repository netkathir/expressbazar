<?php

namespace App\Support;

use App\Models\Order;
use App\Models\OrderItem;

class StoreOfferPricing
{
    public static function cartItemBaseUnit(array $item): float
    {
        $productPrice = (float) ($item['product']->price ?? 0);

        return $productPrice > 0 ? $productPrice : self::cartItemOfferUnit($item);
    }

    public static function cartItemOfferUnit(array $item): float
    {
        return (float) ($item['unit_price'] ?? 0);
    }

    public static function cartItemSavings(array $item): float
    {
        $quantity = max(1, (int) ($item['quantity'] ?? 1));

        return self::unitSavings(self::cartItemBaseUnit($item), self::cartItemOfferUnit($item)) * $quantity;
    }

    public static function cartSavings(iterable $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $total += self::cartItemSavings($item);
        }

        return $total;
    }

    public static function orderItemBaseUnit(OrderItem $item): float
    {
        $productPrice = (float) ($item->product?->price ?? 0);

        return $productPrice > 0 ? $productPrice : self::orderItemOfferUnit($item);
    }

    public static function orderItemOfferUnit(OrderItem $item): float
    {
        return (float) $item->price;
    }

    public static function orderItemSavings(OrderItem $item): float
    {
        return self::unitSavings(self::orderItemBaseUnit($item), self::orderItemOfferUnit($item)) * max(1, (int) $item->quantity);
    }

    public static function orderSavings(Order $order): float
    {
        return $order->items->sum(fn (OrderItem $item) => self::orderItemSavings($item));
    }

    public static function discountLabel(?object $product, float $baseUnit, float $offerUnit): ?string
    {
        if ($baseUnit <= $offerUnit) {
            return null;
        }

        $discountValue = (float) ($product->discount_value ?? 0);

        if (($product->discount_type ?? null) === 'percentage' && $discountValue > 0) {
            return rtrim(rtrim(number_format($discountValue, 2, '.', ''), '0'), '.').'% off';
        }

        if (($product->discount_type ?? null) === 'fixed' && $discountValue > 0) {
            return StoreCurrency::format(min($discountValue, $baseUnit - $offerUnit), 0).' off';
        }

        return 'Save '.StoreCurrency::format($baseUnit - $offerUnit, 0);
    }

    private static function unitSavings(float $baseUnit, float $offerUnit): float
    {
        return max(0, $baseUnit - $offerUnit);
    }
}
