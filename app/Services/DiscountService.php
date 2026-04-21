<?php

namespace App\Services;

class DiscountService
{
    public function calculate(array $lines, ?array $vendor = null, ?array $coupon = null): array
    {
        $lineResults = [];
        $subtotal = 0;
        $discountTotal = 0;

        foreach ($lines as $line) {
            $unitPrice = (int) $line['unit_price'];
            $quantity = (int) $line['quantity'];
            $original = $unitPrice * $quantity;
            $lineDiscount = 0;
            $steps = [];

            if (! empty($line['product_discount_percent'])) {
                $discount = (int) round($original * ($line['product_discount_percent'] / 100));
                $lineDiscount += $discount;
                $steps[] = ['label' => 'Product discount', 'amount' => $discount];
            }

            if (! empty($line['product_discount_amount'])) {
                $discount = (int) $line['product_discount_amount'] * $quantity;
                $lineDiscount += $discount;
                $steps[] = ['label' => 'Product offer', 'amount' => $discount];
            }

            $lineResults[] = [
                'product_id' => $line['product_id'],
                'name' => $line['name'],
                'slug' => $line['slug'] ?? null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'original_total' => $original,
                'discount_total' => $lineDiscount,
                'final_total' => max(0, $original - $lineDiscount),
                'discount_steps' => $steps,
                'image' => $line['image'] ?? null,
                'unit' => $line['unit'] ?? null,
            ];

            $subtotal += $original;
            $discountTotal += $lineDiscount;
        }

        $vendorDiscount = 0;
        if ($vendor && ! empty($vendor['discount_percent'])) {
            $vendorDiscount = (int) round(($subtotal - $discountTotal) * ($vendor['discount_percent'] / 100));
        }

        if ($vendor && ! empty($vendor['discount_amount'])) {
            $vendorDiscount = (int) $vendor['discount_amount'];
        }

        $discountTotal += $vendorDiscount;

        $cartDiscount = 0;
        if (! empty($vendor['cart_discount_percent'])) {
            $cartDiscount = (int) round(($subtotal - $discountTotal) * ($vendor['cart_discount_percent'] / 100));
            $discountTotal += $cartDiscount;
        }

        $couponDiscount = 0;
        if ($coupon) {
            $preCouponTotal = max(0, $subtotal - $discountTotal);
            if (($coupon['type'] ?? null) === 'percentage') {
                $couponDiscount = (int) round($preCouponTotal * ((int) $coupon['value'] / 100));
            } else {
                $couponDiscount = (int) $coupon['value'];
            }

            if (! empty($coupon['max_discount_amount'])) {
                $couponDiscount = min($couponDiscount, (int) $coupon['max_discount_amount']);
            }

            if (! empty($coupon['min_order_amount']) && $preCouponTotal < (int) $coupon['min_order_amount']) {
                $couponDiscount = 0;
            }
        }

        $discountTotal += $couponDiscount;
        $grandTotal = max(0, $subtotal - $discountTotal);

        return [
            'items' => $lineResults,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'vendor_discount' => $vendorDiscount,
            'cart_discount' => $cartDiscount,
            'coupon_discount' => $couponDiscount,
            'grand_total' => $grandTotal,
        ];
    }
}
