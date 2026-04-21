<?php

namespace App\Http\Controllers;

use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\VendorProduct;
use App\Services\CartService;
use App\Services\MarketplaceCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __construct(
        private readonly MarketplaceCatalogService $catalog,
        private readonly CartService $cartService,
    ) {
    }

    public function home(Request $request): View
    {
        $locationId = $this->resolveLocationId($request);
        $categories = $this->catalog->categories();
        $subcategories = $this->catalog->subcategories();
        $vendors = $this->catalog->vendorsForLocation($locationId);
        $products = $this->catalog->productsForLocation($locationId);
        $subCategorySections = $this->buildSubcategorySections($products, $subcategories, 3);

        return view('storefront.home', $this->storefrontData([
            'pageTitle' => 'ExpressBazar | Grocery marketplace',
            'pageDescription' => 'Simple multi-vendor grocery shopping with location filtering.',
            'activeNav' => 'home',
            'selectedLocationId' => $locationId,
            'selectedLocation' => $this->selectedLocationLabel($locationId),
            'categories' => $categories,
            'subcategories' => $subcategories,
            'featuredSections' => $subCategorySections,
            'vendors' => $vendors,
            'products' => $products,
            'vendorCount' => count($vendors),
            'productCount' => count($products),
            'trendingRows' => [
                'Categories' => ['Fresh Produce', 'Dairy & Bakery', 'Pantry Staples', 'Masala & Dry Fruits', 'Breakfast & Sauces'],
                'Products' => ['Tomato', 'Milk', 'Bread', 'Onion', 'Potato', 'Lemon', 'Rice'],
                'Brands' => ['Amul', 'Britannia', 'Nandini', 'Fortune', 'Tata', 'Aashirvaad'],
            ],
            'popularRows' => [
                'Products' => ['Avocado', 'Coconut Water', 'Diet Coke', 'Lettuce', 'Butter', 'Paneer'],
                'Brands' => ['Yakult', 'My Muse', 'Keventer', 'Dermicool', 'Lays', 'Vim'],
                'Categories' => ['Grocery', 'Fresh fruits', 'Fresh vegetables', 'Curd', 'Butter price', 'Paneer price'],
            ],
            'topTabs' => [
                ['label' => 'All', 'icon' => null, 'anchor' => '#categories', 'active' => true],
                ['label' => 'Cafe', 'icon' => null, 'anchor' => '#subcategories'],
                ['label' => 'Home', 'icon' => null, 'anchor' => '#featured-rails'],
                ['label' => 'Toys', 'icon' => null, 'anchor' => '#featured-rails'],
                ['label' => 'Fresh', 'icon' => null, 'anchor' => '#featured-rails'],
                ['label' => 'Electronics', 'icon' => null, 'anchor' => '#popular-searches'],
                ['label' => 'Mobiles', 'icon' => null, 'anchor' => '#popular-searches'],
                ['label' => 'Beauty', 'icon' => null, 'anchor' => '#popular-searches'],
                ['label' => 'Fashion', 'icon' => null, 'anchor' => '#popular-searches'],
            ],
        ]));
    }

    public function browseAll(Request $request): View
    {
        $locationId = $this->resolveLocationId($request);
        $subcategories = $this->catalog->subcategories();
        $sections = $this->buildSubcategorySections($this->catalog->productsForLocation($locationId), $subcategories, null);

        return view('storefront.browse', $this->storefrontData([
            'pageTitle' => 'All subcategory products | ExpressBazar',
            'pageDescription' => 'Browse all subcategory products.',
            'activeNav' => 'home',
            'selectedLocationId' => $locationId,
            'selectedLocation' => $this->selectedLocationLabel($locationId),
            'subcategories' => $subcategories,
            'featuredSections' => $sections,
            'topTabs' => [
                ['label' => 'All', 'icon' => null, 'anchor' => '#categories', 'active' => true],
                ['label' => 'Cafe', 'icon' => null, 'anchor' => '#subcategories'],
                ['label' => 'Home', 'icon' => null, 'anchor' => '#featured-rails'],
                ['label' => 'Toys', 'icon' => null, 'anchor' => '#featured-rails'],
                ['label' => 'Fresh', 'icon' => null, 'anchor' => '#featured-rails'],
                ['label' => 'Electronics', 'icon' => null, 'anchor' => '#popular-searches'],
                ['label' => 'Mobiles', 'icon' => null, 'anchor' => '#popular-searches'],
                ['label' => 'Beauty', 'icon' => null, 'anchor' => '#popular-searches'],
                ['label' => 'Fashion', 'icon' => null, 'anchor' => '#popular-searches'],
            ],
        ]));
    }

    public function category(string $slug, Request $request): View
    {
        $category = $this->catalog->categoryBySlug($slug);
        abort_if(! $category, 404);

        $locationId = $this->resolveLocationId($request);
        $subcategories = collect($this->catalog->subcategories())
            ->filter(fn (array $item) => ($item['category_slug'] ?? null) === $slug)
            ->values()
            ->all();
        $products = $this->catalog->productsForCategory($slug, $locationId);
        $sections = $this->buildSubcategorySections($products, $subcategories, null);

        return view('storefront.category', $this->storefrontData([
            'pageTitle' => $category['name'] . ' | ExpressBazar',
            'pageDescription' => $category['description'] ?? $category['name'],
            'activeNav' => 'home',
            'selectedLocationId' => $locationId,
            'selectedLocation' => $this->selectedLocationLabel($locationId),
            'category' => $category,
            'subcategories' => $subcategories,
            'featuredSections' => $sections,
            'products' => $products,
        ]));
    }

    public function subcategory(string $slug, Request $request): View
    {
        $subcategory = $this->catalog->subcategoryBySlug($slug);
        abort_if(! $subcategory, 404);

        $locationId = $this->resolveLocationId($request);
        $products = $this->catalog->productsForSubcategory($slug, $locationId);
        $category = $subcategory['category_slug'] ? $this->catalog->categoryBySlug($subcategory['category_slug']) : null;

        return view('storefront.subcategory', $this->storefrontData([
            'pageTitle' => $subcategory['name'] . ' | ExpressBazar',
            'pageDescription' => $subcategory['description'] ?? $subcategory['name'],
            'activeNav' => 'home',
            'selectedLocationId' => $locationId,
            'selectedLocation' => $this->selectedLocationLabel($locationId),
            'subcategory' => $subcategory,
            'category' => $category,
            'products' => $products,
        ]));
    }

    public function vendor(string $slug, Request $request): View
    {
        $vendor = $this->catalog->findVendorBySlug($slug);
        abort_if(! $vendor, 404);

        $locationId = $this->resolveLocationId($request);
        $allowedVendorIds = collect($this->catalog->vendorsForLocation($locationId))->pluck('id')->all();
        $products = in_array((int) $vendor['id'], $allowedVendorIds, true)
            ? $this->catalog->productsForVendor((int) $vendor['id'])
            : [];

        return view('storefront.vendor', $this->storefrontData([
            'pageTitle' => $vendor['name'] . ' | ExpressBazar',
            'pageDescription' => $vendor['description'] ?? $vendor['name'],
            'activeNav' => 'vendors',
            'vendor' => $vendor,
            'selectedLocationId' => $locationId,
            'products' => $products,
        ]));
    }

    public function product(string $slug, Request $request): View
    {
        $product = $this->catalog->findProductBySlug($slug);
        abort_if(! $product, 404);

        $locationId = $this->resolveLocationId($request);
        $allowedVendorIds = collect($this->catalog->vendorsForLocation($locationId))->pluck('id')->all();
        $vendorProducts = collect($this->catalog->vendorProducts())
            ->filter(fn (array $item) => (int) $item['product_id'] === (int) $product['id'] && in_array((int) $item['vendor_id'], $allowedVendorIds, true));
        $relatedProducts = $product['subcategory_slug'] ?? null
            ? collect($this->catalog->productsForSubcategory($product['subcategory_slug'], $locationId))
                ->reject(fn (array $item) => (int) $item['product_id'] === (int) $product['id'])
                ->take(6)
                ->values()
                ->all()
            : [];
        $subcategory = $product['subcategory_slug'] ?? null ? $this->catalog->subcategoryBySlug($product['subcategory_slug']) : null;
        $category = $subcategory && ! empty($subcategory['category_slug']) ? $this->catalog->categoryBySlug($subcategory['category_slug']) : null;

        return view('storefront.product', $this->storefrontData([
            'pageTitle' => $product['name'] . ' | ExpressBazar',
            'pageDescription' => $product['description'] ?? $product['name'],
            'activeNav' => 'products',
            'product' => $product,
            'subcategory' => $subcategory,
            'category' => $category,
            'selectedLocationId' => $locationId,
            'vendorProducts' => $vendorProducts->values()->all(),
            'relatedProducts' => $relatedProducts,
        ]));
    }

    public function cart(): View
    {
        $pricing = $this->pricing();

        return view('storefront.cart', $this->storefrontData([
            'pageTitle' => 'Cart | ExpressBazar',
            'pageDescription' => 'Review your selected items before checkout.',
            'activeNav' => 'cart',
            'cartItems' => $pricing['items'],
            'orderSummary' => $pricing,
        ]));
    }

    public function checkout(Request $request): View|RedirectResponse
    {
        $pricing = $this->pricing();

        if ($pricing['items'] === []) {
            return redirect()->route('home')->with('status', 'Your cart is empty.');
        }

        return view('storefront.checkout', $this->storefrontData([
            'pageTitle' => 'Checkout | ExpressBazar',
            'pageDescription' => 'Enter delivery details and place your order.',
            'activeNav' => 'checkout',
            'cartItems' => $pricing['items'],
            'orderSummary' => $pricing,
            'oldAddress' => $request->old('shipping_address', ''),
        ]));
    }

    public function applyCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'coupon_code' => ['required', 'string', 'max:50'],
        ]);

        $pricing = $this->pricing();
        if ($pricing['items'] === []) {
            return redirect()->route('cart.show')->with('status', 'Add items to your cart first.');
        }

        $coupon = $this->catalog->couponByCode($data['coupon_code']);
        if (! $coupon || ! $this->isCouponUsable($coupon, $pricing['subtotal'])) {
            return redirect()->route('cart.show')->withErrors(['coupon_code' => 'This coupon is not valid for your cart.']);
        }

        $this->cartService->applyCoupon($coupon['code']);

        return redirect()->route('cart.show')->with('status', 'Coupon applied successfully.');
    }

    public function removeCoupon(): RedirectResponse
    {
        $this->cartService->removeCoupon();

        return redirect()->route('cart.show')->with('status', 'Coupon removed.');
    }

    public function placeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'pincode' => ['required', 'string', 'max:20'],
        ]);

        $pricing = $this->pricing();
        if ($pricing['items'] === []) {
            return redirect()->route('home')->with('status', 'Your cart is empty.');
        }

        $order = DB::transaction(function () use ($data, $pricing): Order {
            $order = Order::create([
                'user_id' => auth()->id(),
                'vendor_id' => $pricing['vendor_id'],
                'order_number' => 'ORD-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'status' => 'placed',
                'subtotal' => $pricing['subtotal'],
                'discount_total' => $pricing['discount_total'],
                'delivery_fee' => 0,
                'grand_total' => $pricing['grand_total'],
                'currency' => 'INR',
                'shipping_name' => $data['shipping_name'],
                'shipping_phone' => $data['shipping_phone'],
                'shipping_address' => $data['shipping_address'] . ' - ' . $data['pincode'],
                'payment_status' => 'pending',
            ]);

            foreach ($pricing['items'] as $item) {
                $vendorProduct = VendorProduct::query()
                    ->where('vendor_id', $item['vendor_id'])
                    ->where('product_id', $item['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $vendorProduct || $vendorProduct->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'shipping_address' => 'One or more items are out of stock.',
                    ]);
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);

                $vendorProduct->decrement('stock', $item['quantity']);
            }

            if ($pricing['coupon'] !== null) {
                CouponUsage::create([
                    'coupon_id' => $pricing['coupon']['id'],
                    'user_id' => auth()->id(),
                    'order_id' => $order->id,
                    'discount_amount' => $pricing['discount_total'],
                ]);
            }

            Payment::create([
                'order_id' => $order->id,
                'provider' => 'cod',
                'amount' => $pricing['grand_total'],
                'currency' => 'INR',
                'status' => 'pending',
                'payload' => ['payment_method' => 'cod'],
            ]);

            return $order;
        });

        $this->cartService->clear();

        return redirect()->route('order.success', $order->order_number)->with('status', 'Order placed successfully.');
    }

    public function success(string $orderNumber): View
    {
        $order = Order::query()->with(['items.product', 'vendor', 'user'])->where('order_number', $orderNumber)->firstOrFail();

        return view('storefront.success', $this->storefrontData([
            'pageTitle' => 'Order confirmed | ExpressBazar',
            'pageDescription' => 'Your order has been confirmed.',
            'activeNav' => 'checkout',
            'order' => $order,
        ]));
    }

    public function myOrders(Request $request): View
    {
        abort_unless(auth()->check(), 403);

        $orders = Order::query()
            ->with(['items.product', 'vendor'])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('storefront.orders', $this->storefrontData([
            'pageTitle' => 'My Orders | ExpressBazar',
            'pageDescription' => 'Track your ExpressBazar orders.',
            'activeNav' => 'orders',
            'selectedLocationId' => $this->resolveLocationId($request),
            'orders' => $orders,
        ]));
    }

    public function addToCart(Request $request, string $slug): RedirectResponse
    {
        $product = $this->catalog->findProductBySlug($slug);
        abort_if(! $product, 404);

        $vendorProductId = (int) $request->input('vendor_product_id');
        $vendorProduct = collect($this->catalog->vendorProducts())->firstWhere('id', $vendorProductId);

        if (! $vendorProduct || (int) $vendorProduct['product_id'] !== (int) $product['id']) {
            return redirect()->back()->with('status', 'Please choose a vendor for this product.');
        }

        $currentVendorId = $this->cartService->vendorId();
        if ($currentVendorId !== null && (int) $currentVendorId !== (int) $vendorProduct['vendor_id']) {
            $this->cartService->clear();
        }

        $this->cartService->add([
            'id' => $vendorProduct['id'],
            'vendor_product_id' => $vendorProduct['id'],
            'product_id' => $product['id'],
            'vendor_id' => $vendorProduct['vendor_id'],
            'vendor_name' => $vendorProduct['vendor_name'],
            'name' => $product['name'],
            'slug' => $product['slug'],
            'price' => $vendorProduct['price'],
            'image' => $vendorProduct['image'],
        ], (int) $request->input('quantity', 1));

        return redirect()->back()->with('status', 'Added to cart.');
    }

    public function removeFromCart(int $productId): RedirectResponse
    {
        $this->cartService->remove($productId);

        return redirect()->route('cart.show')->with('status', 'Item removed from cart.');
    }

    public function clearCart(): RedirectResponse
    {
        $this->cartService->clear();

        return redirect()->route('cart.show')->with('status', 'Cart cleared.');
    }

    private function pricing(): array
    {
        $items = [];
        $vendorId = null;

        foreach ($this->cartService->lines() as $line) {
            $vendorId = $vendorId ?: (int) $line['vendor_id'];
            $items[] = [
                'vendor_id' => (int) $line['vendor_id'],
                'product_id' => (int) $line['product_id'],
                'vendor_product_id' => (int) ($line['vendor_product_id'] ?? 0),
                'name' => $line['name'],
                'slug' => $line['slug'],
                'vendor_name' => $line['vendor_name'] ?? '',
                'quantity' => (int) $line['quantity'],
                'unit_price' => (int) $line['unit_price'],
                'line_total' => (int) $line['unit_price'] * (int) $line['quantity'],
                'image' => $line['image'] ?? null,
            ];
        }

        $subtotal = collect($items)->sum('line_total');
        $couponCode = $this->cartService->couponCode();
        $coupon = $couponCode ? $this->catalog->couponByCode($couponCode) : null;
        $discountTotal = 0;

        if ($coupon !== null && $this->isCouponUsable($coupon, $subtotal)) {
            $discountTotal = $this->calculateCouponDiscount($coupon, $subtotal);
        } elseif ($couponCode !== null) {
            $this->cartService->removeCoupon();
            $coupon = null;
        }

        $grandTotal = max(0, $subtotal - $discountTotal);

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'grand_total' => $grandTotal,
            'vendor_id' => $vendorId,
            'coupon' => $coupon,
            'coupon_code' => $coupon['code'] ?? null,
        ];
    }

    private function resolveLocationId(Request $request): ?int
    {
        $locationId = $request->integer('location_id', (int) session('selected_location_id', 0));

        if ($request->filled('location_id')) {
            session(['selected_location_id' => $locationId]);
        }

        return $locationId ?: null;
    }

    private function selectedLocationLabel(?int $locationId): string
    {
        if (! $locationId) {
            return 'Choose location';
        }

        $location = collect($this->catalog->locations())->firstWhere('id', $locationId);

        return $location['label'] ?? 'Choose location';
    }

    private function storefrontData(array $overrides = []): array
    {
        $selectedLocationId = $overrides['selectedLocationId'] ?? session('selected_location_id');

        return array_merge([
            'brandName' => config('app.name', 'ExpressBazar'),
            'cartCount' => collect($this->cartService->lines())->sum('quantity'),
            'locations' => $this->catalog->locations(),
            'selectedLocationId' => $selectedLocationId,
            'selectedLocation' => $this->selectedLocationLabel($selectedLocationId ? (int) $selectedLocationId : null),
            'navItems' => [
                ['label' => 'All', 'slug' => 'all', 'anchor' => '#categories', 'icon' => null],
                ['label' => 'Cafe', 'slug' => 'cafe', 'anchor' => '#subcategories', 'icon' => null],
                ['label' => 'Home', 'slug' => 'home', 'anchor' => '#featured-rails', 'icon' => null],
                ['label' => 'Toys', 'slug' => 'toys', 'anchor' => '#featured-rails', 'icon' => null],
                ['label' => 'Fresh', 'slug' => 'fresh', 'anchor' => '#featured-rails', 'icon' => null],
                ['label' => 'Beauty', 'slug' => 'beauty', 'anchor' => '#popular-searches', 'icon' => null],
                ['label' => 'Fashion', 'slug' => 'fashion', 'anchor' => '#popular-searches', 'icon' => null],
            ],
            'footerLinks' => ['Home', 'Delivery Areas', 'Customer Support', 'Privacy Policy', 'Terms of Use'],
            'pageTitle' => config('app.name', 'ExpressBazar'),
        ], $overrides);
    }

    private function buildSubcategorySections(array $products, array $subcategories, ?int $limit): array
    {
        $grouped = collect($products)->groupBy('subcategory_slug');

        return collect($subcategories)
            ->map(fn (array $subcategory) => [
                'subcategory' => $subcategory,
                'products' => $grouped->get($subcategory['slug'], collect())->take(8)->values()->all(),
            ])
            ->filter(fn (array $section) => $section['products'] !== [])
            ->when($limit !== null, fn ($collection) => $collection->take($limit))
            ->values()
            ->all();
    }

    private function isCouponUsable(array $coupon, int $subtotal): bool
    {
        if (! ($coupon['is_active'] ?? false)) {
            return false;
        }

        if (! empty($coupon['starts_at']) && now()->lt($coupon['starts_at'])) {
            return false;
        }

        if (! empty($coupon['ends_at']) && now()->gt($coupon['ends_at'])) {
            return false;
        }

        return $subtotal >= (int) ($coupon['min_order_amount'] ?? 0);
    }

    private function calculateCouponDiscount(array $coupon, int $subtotal): int
    {
        $value = (int) ($coupon['value'] ?? 0);

        if (($coupon['type'] ?? 'percentage') === 'fixed') {
            $discount = $value;
        } else {
            $discount = (int) round(($subtotal * $value) / 100);
        }

        $maxDiscount = $coupon['max_discount_amount'] ?? null;
        if ($maxDiscount !== null) {
            $discount = min($discount, (int) $maxDiscount);
        }

        return max(0, min($subtotal, $discount));
    }
}
