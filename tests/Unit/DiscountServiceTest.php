<?php

namespace Tests\Unit;

use App\Services\DiscountService;
use PHPUnit\Framework\TestCase;

class DiscountServiceTest extends TestCase
{
    public function test_it_applies_discounts_in_order(): void
    {
        $service = new DiscountService();

        $result = $service->calculate([
            [
                'product_id' => 1,
                'name' => 'Rice',
                'slug' => 'rice',
                'quantity' => 2,
                'unit_price' => 100,
                'product_discount_percent' => 10,
                'product_discount_amount' => 0,
            ],
        ], [
            'discount_percent' => 5,
            'cart_discount_percent' => 5,
        ], [
            'type' => 'percentage',
            'value' => 10,
            'max_discount_amount' => 1000,
            'min_order_amount' => 0,
        ]);

        $this->assertSame(200, $result['subtotal']);
        $this->assertSame(54, $result['discount_total']);
        $this->assertSame(146, $result['grand_total']);
    }
}
