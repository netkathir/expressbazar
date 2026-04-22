<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Banner;
use App\Models\DeliveryConfig;
use App\Models\City;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\RegionZone;
use App\Models\Subcategory;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class StorefrontController extends Controller
{
    public function home(Request $request)
    {
        return view('storefront.home', $this->storefrontData($request, 'Home'));
    }

    public function category(Request $request, Category $category)
    {
        return view('storefront.category', $this->storefrontData($request, $category->category_name, [
            'category' => $category->loadMissing('subcategories'),
            'products' => $this->categoryProducts($category),
        ]));
    }

    public function subcategory(Request $request, Subcategory $subcategory)
    {
        return view('storefront.subcategory', $this->storefrontData($request, $subcategory->subcategory_name, [
            'subcategory' => $subcategory->loadMissing('category'),
            'products' => $this->subcategoryProducts($subcategory),
        ]));
    }

    public function product(Request $request, Product $product)
    {
        $product->load(['category', 'subcategory', 'vendor', 'images', 'inventory', 'tax']);

        return view('storefront.product', $this->storefrontData($request, $product->product_name, [
            'product' => $product,
            'relatedProducts' => $this->relatedProducts($product),
        ]));
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->string('q'));

        return view('storefront.search', $this->storefrontData($request, 'Search Results', [
            'keyword' => $keyword,
            'searchResults' => $this->searchResults($keyword),
        ]));
    }

    public function cart(Request $request)
    {
        return view('storefront.cart', $this->storefrontData($request, 'Your Cart'));
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer', 403);

        $addresses = CustomerAddress::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['country', 'city', 'zone'])
            ->latest()
            ->get();

        $selectedAddressId = (int) old('address_id', $addresses->firstWhere('is_default')?->id ?? $addresses->first()?->id ?? 0);
        $selectedAddress = $selectedAddressId
            ? $addresses->firstWhere('id', $selectedAddressId)
            : null;

        $deliveryChargeByAddress = $addresses->mapWithKeys(function (CustomerAddress $address) {
            return [$address->id => $this->deliveryChargeForZone($address->zone_id) ?? 0];
        });

        $deliveryCharge = $selectedAddress?->zone_id ? (float) $deliveryChargeByAddress->get($selectedAddress->id, 0) : 0;

        return view('storefront.checkout', $this->storefrontData($request, 'Checkout', [
            'user' => $user,
            'addresses' => $addresses,
            'selectedAddress' => $selectedAddress,
            'selectedAddressId' => $selectedAddressId ?: null,
            'deliveryChargeByAddress' => $deliveryChargeByAddress,
            'cartTotals' => $this->cartTotals($deliveryCharge),
            'checkoutPaymentMethod' => old('payment_method', 'cod'),
        ]));
    }

    public function placeOrder(Request $request)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer', 403);

        $cartItems = $this->cartItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('storefront.cart')->with('error', 'Your cart is empty.');
        }

        $data = $request->validate([
            'address_id' => ['required', 'integer'],
            'payment_method' => ['required', Rule::in(['cod', 'online'])],
        ]);

        $address = CustomerAddress::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['country', 'city', 'zone'])
            ->findOrFail($data['address_id']);

        if (! $address->zone_id || ! $address->zone || $address->zone->status !== 'active' || ! $address->zone->delivery_available) {
            throw ValidationException::withMessages([
                'address_id' => 'Delivery is not available in your area.',
            ]);
        }

        $deliveryCharge = $this->deliveryChargeForZone($address->zone_id);

        if ($deliveryCharge === null) {
            throw ValidationException::withMessages([
                'address_id' => 'Delivery is not available in your area.',
            ]);
        }

        $location = [
            'country_id' => (int) $address->country_id,
            'city_id' => (int) $address->city_id,
            'zone_id' => (int) $address->zone_id,
        ];

        $vendorId = $this->cartVendorId();
        if (! $vendorId) {
            return redirect()->route('storefront.cart')->with('error', 'Please add items to your cart first.');
        }

        $orderItems = [];
        $itemTotal = 0.0;
        $order = null;

        DB::transaction(function () use ($cartItems, $address, $user, $vendorId, $deliveryCharge, $location, $request, &$orderItems, &$itemTotal, &$order) {
            $orderNumber = $this->generateOrderNumber();

            $freshProducts = Product::query()
                ->with(['vendor', 'inventory'])
                ->whereIn('id', $cartItems->pluck('product.id')->all())
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($cartItems as $item) {
                $product = $freshProducts->get($item['product']->id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'cart' => 'One or more cart items are no longer available.',
                    ]);
                }

                if ($product->status !== 'active') {
                    throw ValidationException::withMessages([
                        'cart' => "{$product->product_name} is currently unavailable.",
                    ]);
                }

                if ((int) $product->vendor_id !== (int) $vendorId || $product->vendor?->status !== 'active') {
                    throw ValidationException::withMessages([
                        'cart' => 'Your cart contains an unavailable vendor item.',
                    ]);
                }

                if ((int) $product->vendor?->region_zone_id !== (int) $address->zone_id) {
                    throw ValidationException::withMessages([
                        'address_id' => 'Selected address does not match the vendor delivery zone.',
                    ]);
                }

                $inventory = ProductInventory::query()
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (! $inventory) {
                    throw ValidationException::withMessages([
                        'cart' => "{$product->product_name} inventory is missing.",
                    ]);
                }

                $quantity = (int) $item['quantity'];
                $available = (int) $inventory->stock_quantity;

                if ($available < $quantity) {
                    throw ValidationException::withMessages([
                        'cart' => "{$product->product_name} only has {$available} item(s) left.",
                    ]);
                }

                $price = (float) ($product->final_price ?: $product->price);
                $subtotal = $price * $quantity;
                $itemTotal += $subtotal;

                $previousStock = $available;
                $newStock = $available - $quantity;
                $inventory->stock_quantity = $newStock;
                $inventory->sync_status = $inventory->inventory_mode === 'epos' ? 'pending' : $inventory->sync_status;
                $inventory->last_synced_at = now();
                $inventory->save();

                InventoryLog::create([
                    'product_id' => $product->id,
                    'product_inventory_id' => $inventory->id,
                    'change_type' => 'order_placed',
                    'quantity' => $quantity,
                    'previous_stock' => $previousStock,
                    'new_stock' => $newStock,
                    'source' => $inventory->inventory_mode,
                    'reason' => 'Customer checkout order placement',
                ]);

                $orderItems[] = [
                    'product_id' => $product->id,
                    'item_name' => $product->product_name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $grandTotal = $itemTotal + $deliveryCharge;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $user->id,
                'vendor_id' => $vendorId,
                'total_amount' => $grandTotal,
                'delivery_charge' => $deliveryCharge,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'placed_at' => now(),
                'notes' => 'Placed from storefront checkout. Delivery to zone '.$location['zone_id'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($orderItems as $orderItem) {
                $order->items()->create($orderItem);
            }

            Payment::create([
                'order_id' => $order->id,
                'transaction_id' => $this->generateTransactionId(),
                'payment_method' => $request->string('payment_method')->toString(),
                'amount' => $grandTotal,
                'status' => 'pending',
                'gateway_response' => json_encode([
                    'source' => 'checkout',
                    'payment_method' => $request->string('payment_method')->toString(),
                    'payment_status' => 'pending',
                ]),
            ]);
        });

        session()->put('storefront.location', $location);
        session()->forget('storefront.soft_location');
        $this->clearCartState();

        return redirect()
            ->route('storefront.orders.success', $order)
            ->with('success', 'Order '.$order->order_number.' placed successfully. Payment is pending.');
    }

    public function mergeGuestCart(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer', 403);

        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cart = session()->get('storefront.cart', []);
        $currentVendorId = $this->cartVendorId();

        $products = Product::query()
            ->with(['vendor', 'inventory'])
            ->whereIn('id', collect($data['items'])->pluck('product_id')->all())
            ->get()
            ->keyBy('id');

        foreach ($data['items'] as $item) {
            $product = $products->get((int) $item['product_id']);

            if (! $product) {
                continue;
            }

            $this->ensureAddable($product);

            if (! empty($cart) && $currentVendorId && (int) $product->vendor_id !== $currentVendorId) {
                throw ValidationException::withMessages([
                    'items' => 'Your guest cart contains items from another vendor. Please clear cart to continue.',
                ]);
            }

            $currentQuantity = (int) ($cart[$product->id]['quantity'] ?? 0);
            $cart[$product->id] = [
                'quantity' => $currentQuantity + (int) $item['quantity'],
            ];

            $currentVendorId = (int) $product->vendor_id;
        }

        session()->put('storefront.cart', $cart);
        if ($currentVendorId) {
            session()->put('storefront.cart_vendor_id', $currentVendorId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Guest cart merged successfully.',
            'cartCount' => $this->cartCount(),
            'drawerHtml' => $this->renderCartDrawer(),
            'cartState' => $this->cartState(),
        ]);
    }

    public function setLocation(Request $request)
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'zone_id' => ['nullable', 'exists:regions_zones,id'],
            'postcode' => ['nullable', 'string', 'max:32'],
            'force_clear' => ['nullable'],
        ]);

        if (! empty($data['postcode']) && empty($data['zone_id'])) {
            $zone = RegionZone::query()
                ->whereRaw('LOWER(zone_code) = ?', [mb_strtolower(trim($data['postcode']))])
                ->where('status', 'active')
                ->where('delivery_available', true)
                ->first();

            if (! $zone) {
                throw ValidationException::withMessages([
                    'postcode' => 'Delivery is not available in your area.',
                ]);
            }

            $data['zone_id'] = $zone->id;
            $data['city_id'] = $zone->city_id;
            $data['country_id'] = $zone->country_id;
        }

        $city = City::findOrFail($data['city_id']);

        if ((int) $city->country_id !== (int) $data['country_id']) {
            throw ValidationException::withMessages([
                'city_id' => 'Selected city must belong to the selected country.',
            ]);
        }

        if (! empty($data['zone_id'])) {
            $zone = RegionZone::query()
                ->where('id', $data['zone_id'])
                ->where('country_id', $data['country_id'])
                ->where('city_id', $data['city_id'])
                ->where('status', 'active')
                ->where('delivery_available', true)
                ->first();

            if (! $zone) {
                throw ValidationException::withMessages([
                    'zone_id' => 'Delivery is not available in your area.',
                ]);
            }
        }

        $newLocation = [
            'country_id' => (int) $data['country_id'],
            'city_id' => (int) $data['city_id'],
            'zone_id' => isset($data['zone_id']) ? (int) $data['zone_id'] : null,
        ];

        $locationChanged = $this->hardLocation() !== $newLocation;

        if ($locationChanged && $this->cartCount() > 0 && ! $request->boolean('force_clear')) {
            return response()->json([
                'needs_confirmation' => true,
                'message' => 'Changing your location will clear your cart. Do you want to continue?',
            ], 422);
        }

        if ($locationChanged && $this->cartCount() > 0) {
            $this->clearCartState();
        }

        session()->put('storefront.location', $newLocation);
        session()->forget('storefront.soft_location');

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'locationLabel' => $this->locationLabel(),
                'cartCount' => $this->cartCount(),
                'drawerHtml' => $this->renderCartDrawer(),
            ]);
        }

        return back()->with('success', 'Location updated.');
    }

    public function cities(Request $request): JsonResponse
    {
        $cities = City::query()
            ->where('status', 'active')
            ->when($request->filled('country_id'), function ($query) use ($request) {
                $query->where('country_id', $request->integer('country_id'));
            })
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'country_id']);

        return response()->json(['cities' => $cities]);
    }

    public function zones(Request $request): JsonResponse
    {
        $zones = RegionZone::query()
            ->where('status', 'active')
            ->where('delivery_available', true)
            ->when($request->filled('city_id'), function ($query) use ($request) {
                $query->where('city_id', $request->integer('city_id'));
            })
            ->orderBy('zone_name')
            ->get(['id', 'zone_name', 'zone_code', 'city_id']);

        return response()->json(['zones' => $zones]);
    }

    public function addToCart(Request $request, Product $product): JsonResponse
    {
        $this->ensureAddable($product);

        $quantity = max(1, (int) $request->input('quantity', 1));
        $cart = session()->get('storefront.cart', []);
        $currentVendorId = $this->cartVendorId();
        $productVendorId = (int) $product->vendor_id;

        if (! empty($cart) && $currentVendorId && $currentVendorId !== $productVendorId) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart contains items from another vendor. Please clear cart to continue.',
                'cartCount' => $this->cartCount(),
                'drawerHtml' => $this->renderCartDrawer(),
            ], 422);
        }

        $currentQuantity = (int) ($cart[$product->id]['quantity'] ?? 0);
        $cart[$product->id] = [
            'quantity' => $currentQuantity + $quantity,
        ];

        session()->put('storefront.cart', $cart);
        session()->put('storefront.cart_vendor_id', $productVendorId);

        return response()->json([
            'success' => true,
            'message' => 'Added to cart',
            'cartCount' => $this->cartCount(),
            'drawerHtml' => $this->renderCartDrawer(),
            'cartState' => $this->cartState(),
        ]);
    }

    public function updateCart(Request $request, Product $product): JsonResponse
    {
        $delta = (int) $request->input('delta', 0);
        $cart = session()->get('storefront.cart', []);

        if (! isset($cart[$product->id])) {
            return response()->json(['success' => false], 404);
        }

        $newQuantity = max(0, (int) $cart[$product->id]['quantity'] + $delta);

        if ($newQuantity === 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id]['quantity'] = $newQuantity;
        }

        session()->put('storefront.cart', $cart);

        if (empty($cart)) {
            session()->forget('storefront.cart_vendor_id');
        }

        return response()->json([
            'success' => true,
            'cartCount' => $this->cartCount(),
            'drawerHtml' => $this->renderCartDrawer(),
            'cartState' => $this->cartState(),
        ]);
    }

    public function removeFromCart(Product $product): JsonResponse
    {
        $cart = session()->get('storefront.cart', []);
        unset($cart[$product->id]);
        session()->put('storefront.cart', $cart);

        if (empty($cart)) {
            session()->forget('storefront.cart_vendor_id');
        }

        return response()->json([
            'success' => true,
            'cartCount' => $this->cartCount(),
            'drawerHtml' => $this->renderCartDrawer(),
            'cartState' => $this->cartState(),
        ]);
    }

    public function clearCart(): JsonResponse
    {
        $this->clearCartState();

        return response()->json([
            'success' => true,
            'cartCount' => 0,
            'drawerHtml' => $this->renderCartDrawer(),
            'cartState' => [],
        ]);
    }

    private function storefrontData(Request $request, string $title, array $extra = []): array
    {
        $location = $this->browsingLocation();
        $featuredSections = $this->featuredSections($location);
        $categories = Category::query()
            ->where('status', 'active')
            ->withCount('products')
            ->orderBy('category_name')
            ->get();
        $cartItems = $this->cartItems();

        return array_merge([
            'title' => $title,
            'location' => $location,
            'locationLabel' => $this->locationLabel(),
            'cartCount' => $this->cartCount(),
            'cartItems' => $cartItems,
            'cartMap' => $cartItems->keyBy(fn ($item) => $item['product']->id),
            'cartState' => $this->cartState(),
            'cartTotals' => $this->cartTotals(),
            'categories' => $categories,
            'featuredSections' => $featuredSections,
            'banners' => Banner::query()
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'allSubcategories' => Subcategory::query()
                ->where('status', 'active')
                ->with('category')
                ->orderBy('subcategory_name')
                ->get(),
            'countries' => Country::query()->where('status', 'active')->orderBy('country_name')->get(),
            'cities' => City::query()->where('status', 'active')->orderBy('city_name')->get(),
            'zones' => RegionZone::query()->where('status', 'active')->orderBy('zone_name')->get(),
        ], $extra);
    }

    private function featuredSections(?array $location): array
    {
        $sections = [];

        $subcategories = Subcategory::query()
            ->where('status', 'active')
            ->with(['category', 'products' => function ($query) use ($location) {
                $this->applyProductScope($query, $location);
            }])
            ->orderBy('subcategory_name')
            ->limit(6)
            ->get();

        foreach ($subcategories as $subcategory) {
            $products = $subcategory->products->take(8);

            if ($products->isNotEmpty()) {
                $sections[] = [
                    'title' => $subcategory->subcategory_name,
                    'subcategory' => $subcategory,
                    'category' => $subcategory->category,
                    'products' => $products,
                ];
            }
        }

        if (empty($sections)) {
            $sections[] = [
                'title' => 'Trending Near You',
                'category' => null,
                'products' => $this->productsQuery($location)->limit(8)->get(),
            ];
        }

        return $sections;
    }

    private function searchResults(string $keyword)
    {
        return $this->productsQuery($this->browsingLocation())
            ->when($keyword !== '', function ($query) use ($keyword) {
                $query->where('product_name', 'like', "%{$keyword}%");
            })
            ->limit(48)
            ->get();
    }

    private function relatedProducts(Product $product)
    {
        return $this->productsQuery($this->browsingLocation())
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->limit(8)
            ->get();
    }

    private function categoryProducts(Category $category)
    {
        return $this->productsQuery($this->browsingLocation())
            ->where('category_id', $category->id)
            ->limit(60)
            ->get();
    }

    private function subcategoryProducts(Subcategory $subcategory)
    {
        return $this->productsQuery($this->browsingLocation())
            ->where('subcategory_id', $subcategory->id)
            ->limit(60)
            ->get();
    }

    private function productsQuery(?array $location)
    {
        $query = Product::query()
            ->with(['category', 'subcategory', 'vendor', 'images', 'inventory', 'tax'])
            ->where('status', 'active');

        $this->applyProductScope($query, $location);

        return $query;
    }

    private function applyProductScope($query, ?array $location): void
    {
        $query->whereHas('vendor', function ($vendorQuery) use ($location) {
            $vendorQuery->where('status', 'active');

            if ($location && ! empty($location['zone_id'])) {
                $vendorQuery->where('region_zone_id', $location['zone_id']);
            } elseif ($location && ! empty($location['city_id'])) {
                $vendorQuery->where('city_id', $location['city_id']);
            }
        });

        $query->where(function ($stockQuery) {
            $stockQuery->whereHas('inventory', function ($inventoryQuery) {
                $inventoryQuery->where('inventory_mode', 'epos');
            })->orWhereHas('inventory', function ($inventoryQuery) {
                $inventoryQuery->where('inventory_mode', 'internal')->where('stock_quantity', '>', 0);
            });
        });
    }

    private function hardLocation(): ?array
    {
        return session('storefront.location');
    }

    private function softLocation(): ?array
    {
        $stored = session('storefront.soft_location');
        if (is_array($stored)) {
            return $stored;
        }

        $city = City::query()
            ->where('status', 'active')
            ->with('country')
            ->orderBy('city_name')
            ->first();

        if (! $city) {
            return null;
        }

        $soft = [
            'country_id' => (int) $city->country_id,
            'city_id' => (int) $city->id,
            'zone_id' => null,
            'mode' => 'soft',
        ];

        session()->put('storefront.soft_location', $soft);

        return $soft;
    }

    private function browsingLocation(): ?array
    {
        return $this->hardLocation() ?? $this->softLocation();
    }

    private function locationLabel(): string
    {
        $location = $this->browsingLocation();

        if (! $location) {
            return 'Select Location';
        }

        $city = City::find($location['city_id']);
        $zone = ! empty($location['zone_id']) ? RegionZone::find($location['zone_id']) : null;

        if ($zone) {
            return $city?->city_name.' · '.$zone->zone_name;
        }

        return $city?->city_name ?? 'Select Location';
    }

    private function cartItems()
    {
        $cart = session()->get('storefront.cart', []);
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

            $quantity = (int) $item['quantity'];
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
            ->map(function (array $item, int $productId) {
                return [
                    'product_id' => $productId,
                    'quantity' => (int) ($item['quantity'] ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function cartCount(): int
    {
        return collect(session()->get('storefront.cart', []))->sum('quantity');
    }

    private function cartVendorId(): ?int
    {
        $vendorId = session('storefront.cart_vendor_id');

        return $vendorId ? (int) $vendorId : null;
    }

    private function cartTotals(?float $deliveryCharge = null): array
    {
        $items = $this->cartItems();
        $itemTotal = $items->sum('subtotal');
        $delivery = $deliveryCharge ?? 0;

        return [
            'itemTotal' => $itemTotal,
            'delivery' => $delivery,
            'grandTotal' => $itemTotal + $delivery,
        ];
    }

    private function deliveryChargeForZone(?int $zoneId): ?float
    {
        if (! $zoneId) {
            return null;
        }

        $config = DeliveryConfig::query()
            ->where('zone_id', $zoneId)
            ->where('status', 'active')
            ->where('delivery_available', true)
            ->first();

        if (! $config) {
            return null;
        }

        return (float) $config->delivery_charge;
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    private function generateTransactionId(): string
    {
        do {
            $transactionId = 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Payment::query()->where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    private function ensureAddable(Product $product): void
    {
        if (! $this->hardLocation()) {
            throw ValidationException::withMessages([
                'location' => 'Please enter your delivery location to add items.',
            ]);
        }

        $product->loadMissing(['vendor', 'inventory']);

        if ($product->vendor?->status !== 'active') {
            throw ValidationException::withMessages(['product' => 'Vendor is currently unavailable.']);
        }

        if ($product->inventory?->inventory_mode === 'internal' && (int) $product->inventory->stock_quantity <= 0) {
            throw ValidationException::withMessages(['product' => 'Product is out of stock.']);
        }
    }

    private function renderCartDrawer(): string
    {
        $cartItems = $this->cartItems();

        return View::make('storefront.partials.cart-drawer', [
            'cartItems' => $cartItems,
            'cartTotals' => $this->cartTotals(),
            'cartCount' => $this->cartCount(),
            'cartMap' => $cartItems->keyBy(fn ($item) => $item['product']->id),
        ])->render();
    }

    private function clearCartState(): void
    {
        session()->forget('storefront.cart');
        session()->forget('storefront.cart_vendor_id');
    }
}
