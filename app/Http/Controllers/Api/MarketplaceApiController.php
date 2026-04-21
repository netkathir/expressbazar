<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Services\DiscountService;
use App\Services\EposNowService;
use App\Services\LocationService;
use App\Services\MarketplaceCatalogService;
use App\Services\OrderService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceApiController extends Controller
{
    public function __construct(
        private readonly MarketplaceCatalogService $catalog,
        private readonly LocationService $locationService,
        private readonly CartService $cartService,
        private readonly DiscountService $discountService,
        private readonly StripeService $stripeService,
        private readonly OrderService $orderService,
        private readonly EposNowService $eposNowService,
    ) {
    }

    public function vendors(Request $request): JsonResponse
    {
        $vendors = $this->catalog->vendors();

        if ($request->filled(['lat', 'lng'])) {
            $vendors = $this->catalog->nearbyVendors(
                (float) $request->query('lat'),
                (float) $request->query('lng'),
                (float) $request->query('radius', 20),
                $this->locationService
            );
        }

        return response()->json(['data' => $vendors]);
    }

    public function products(Request $request): JsonResponse
    {
        $products = $request->filled('vendor_id')
            ? $this->catalog->productsForVendor((int) $request->query('vendor_id'))
            : $this->catalog->products();

        return response()->json([
            'data' => array_map(fn (array $product) => $this->catalog->productSummary($product), $products),
        ]);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = $this->catalog->findProductBySlug($data['slug']);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        $this->cartService->add($product, (int) ($data['quantity'] ?? 1));

        return response()->json(['message' => 'Added to cart.', 'cart' => $this->cartService->items()]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $pricing = $this->calculatePricing();
        $order = $this->orderService->createFromCart($pricing, [
            'shipping_name' => $request->input('shipping_name', 'Customer'),
            'shipping_phone' => $request->input('shipping_phone', ''),
            'shipping_address' => $request->input('shipping_address', ''),
            'delivery_fee' => 0,
            'payment_status' => 'pending',
        ]);

        return response()->json(['data' => $order]);
    }

    public function paymentIntent(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json([
            'data' => $this->stripeService->createPaymentIntent($data['amount'], 'inr'),
        ]);
    }

    public function paymentConfirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        return response()->json([
            'data' => $this->stripeService->confirmPayment($data['payment_intent_id'], $request->all()),
        ]);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $coupon = [
            'code' => strtoupper($data['code']),
            'type' => 'percentage',
            'value' => 10,
            'max_discount_amount' => 200,
            'min_order_amount' => 0,
        ];

        $pricing = $this->calculatePricing($coupon);

        return response()->json([
            'message' => 'Coupon applied.',
            'data' => $pricing,
        ]);
    }

    public function stock(string $productId): JsonResponse
    {
        $product = collect($this->catalog->products())->firstWhere('id', (int) $productId);

        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json(['data' => $this->eposNowService->fetchStock($product['sku'])]);
    }

    private function calculatePricing(?array $coupon = null): array
    {
        $items = [];

        foreach ($this->cartService->lines() as $line) {
            $product = $this->catalog->findProductBySlug($line['slug'] ?? '');

            if (! $product) {
                continue;
            }

            $items[] = [
                'product_id' => $product['id'],
                'name' => $product['name'],
                'quantity' => $line['quantity'],
                'unit_price' => $product['price'],
                'product_discount_percent' => 0,
                'product_discount_amount' => 0,
            ];
        }

        if ($items === []) {
            $fallback = $this->catalog->products()[0];
            $items[] = [
                'product_id' => $fallback['id'],
                'name' => $fallback['name'],
                'quantity' => 1,
                'unit_price' => $fallback['price'],
                'product_discount_percent' => 0,
                'product_discount_amount' => 0,
            ];
        }

        $vendor = collect($this->catalog->vendors())->firstWhere('id', $this->catalog->products()[0]['vendor_id']);

        return $this->discountService->calculate($items, $vendor, $coupon);
    }
}
