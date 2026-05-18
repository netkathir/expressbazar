<?php

namespace App\Http\Controllers;

use App\Events\OrderPlaced;
use App\Models\Category;
use App\Models\Banner;
use App\Models\Coupon;
use App\Models\DeliveryConfig;
use App\Models\City;
use App\Models\ContactInquiry;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\RegionZone;
use App\Models\Subcategory;
use App\Models\Vendor;
use App\Services\InventoryService;
use App\Services\OrderLifecycleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;

class StorefrontController extends Controller
{
    public function home(Request $request)
    {
        $search = trim((string) $request->string('search'));

        if ($search !== '') {
            $this->rememberRecentSearch($search);
        }

        return view('storefront.home', $this->storefrontData($request, 'Home', [
            'search' => $search,
            'searchResults' => $search !== '' ? $this->searchResults($search) : collect(),
        ]));
    }

    public function category(Request $request, Category $category)
    {
        $selectedSubcategory = null;

        if ($request->filled('subcategory')) {
            $selectedSubcategory = $category->subcategories()
                ->where(function ($query) use ($request) {
                    $query->where('id', $request->integer('subcategory'))
                        ->orWhere('subcategory_name', $request->string('subcategory'));
                })
                ->first();
        }

        $data = $this->storefrontData($request, $category->category_name, [
            'category' => $category->loadMissing('subcategories'),
            'selectedSubcategory' => $selectedSubcategory,
            'products' => $this->categoryProducts($category, $selectedSubcategory),
        ]);

        if ($request->ajax()) {
            return $this->productGridResponse($data['products'], config('ui_messages.no_products'));
        }

        return view('storefront.category', $data);
    }

    public function subcategory(Request $request, Subcategory $subcategory)
    {
        $data = $this->storefrontData($request, $subcategory->subcategory_name, [
            'subcategory' => $subcategory->loadMissing('category'),
            'products' => $this->subcategoryProducts($subcategory),
        ]);

        if ($request->ajax()) {
            return $this->productGridResponse($data['products'], config('ui_messages.no_products'));
        }

        return view('storefront.subcategory', $data);
    }

    public function product(Request $request, Product $product)
    {
        $product->load(['category', 'subcategory', 'vendor.country', 'vendor.city', 'vendor.zone', 'images', 'inventory', 'tax']);

        abort_if(! $this->isStorefrontProductAvailable($product), 404);

        return view('storefront.product', $this->storefrontData($request, $product->product_name, [
            'product' => $product,
            'relatedProducts' => $this->relatedProducts($product),
        ]));
    }

    public function search(Request $request)
    {
        $keyword = trim((string) $request->string('q'));
        $requiresLocation = $keyword !== '' && ! $this->hardLocation();
        $products = $requiresLocation ? collect() : $this->searchResults($keyword);

        if ($keyword !== '') {
            $this->rememberRecentSearch($keyword);
        }

        if ($request->ajax()) {
            if ($requiresLocation) {
                return response()->json([
                    'require_location' => true,
                    'message' => 'Enter your delivery location to see exact availability',
                    'recent_searches' => $this->recentSearches(),
                ]);
            }

                return $this->productGridResponse(
                    $products,
                    'No results found',
                    [
                        'recent_searches' => $this->recentSearches(),
                ]
            );
        }

        return view('storefront.search', $this->storefrontData($request, 'Search Results', [
            'keyword' => $keyword,
            'searchResults' => $products,
            'requiresLocation' => $requiresLocation,
            'recentSearches' => $this->recentSearches(),
        ]));
    }

    public function searchSuggestions(Request $request): JsonResponse
    {
        try {
            $keyword = trim((string) $request->string('q'));

            if (mb_strlen($keyword) < 2) {
                return response()->json([]);
            }

            $products = $this->productsQuery($this->browsingLocation())
                ->where(function ($query) use ($keyword) {
                    $query->where('product_name', 'like', "%{$keyword}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                            $categoryQuery->where('status', 'active')
                                ->where('category_name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('subcategory', function ($subcategoryQuery) use ($keyword) {
                            $subcategoryQuery->where('status', 'active')
                                ->where('subcategory_name', 'like', "%{$keyword}%");
                        });
                })
                ->orderBy('product_name')
                ->limit(8)
                ->pluck('product_name');

            return response()->json($products);
        } catch (Throwable $exception) {
            return $this->apiError($exception);
        }
    }

    public function cart(Request $request)
    {
        return view('storefront.cart', $this->storefrontData($request, 'Your Cart'));
    }

    public function contact(Request $request)
    {
        return view('storefront.contact', $this->storefrontData($request, 'Contact Us'));
    }

    public function submitContact(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
        ], [
            'phone.regex' => 'Phone number can contain only numbers, spaces, +, -, and brackets.',
        ]);

        $contactInquiry = null;

        if (Schema::hasTable('contact_inquiries')) {
            $contactInquiry = ContactInquiry::create([
                'user_id' => $request->user()?->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'subject' => $data['subject'],
                'message' => $data['message'],
                'ip_address' => $request->ip(),
                'status' => 'new',
            ]);
        } else {
            Log::warning('Contact inquiry table is missing; submission was not saved to admin inbox.', [
                'email' => $data['email'],
                'subject' => $data['subject'],
                'user_id' => $request->user()?->id,
            ]);
        }

        Log::info('Storefront contact inquiry received.', [
            'contact_inquiry_id' => $contactInquiry?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'],
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
        ]);

        return back()->with('success', 'Thank you for contacting us. We will get back to you soon.');
    }

    public function checkout(Request $request)
    {
        $user = $request->user();

        if (! $user || $user->role !== 'customer') {
            return redirect()
                ->route('storefront.login')
                ->withErrors(['checkout' => 'Please login to continue checkout']);
        }

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
            return redirect()->route('storefront.cart')->with('error', config('ui_messages.empty_cart'));
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

        $deliveryCharge = $this->deliveryChargeForZone($address->zone_id) ?? 0.0;

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
        $payment = null;

        $taxTotal = 0.0;

        DB::transaction(function () use ($cartItems, $address, $user, $vendorId, $deliveryCharge, $location, $request, &$orderItems, &$itemTotal, &$taxTotal, &$order, &$payment) {
            $orderNumber = $this->generateOrderNumber();

            $freshProducts = Product::query()
                ->with(['vendor', 'inventory', 'tax'])
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

                if (! $this->vendorMatchesLocation($product->vendor, $location)) {
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
                $taxAmount = $this->cartItemTax($product, $subtotal);
                $taxTotal += $taxAmount;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'item_name' => $product->product_name,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            }

            $coupon = $this->validCouponForCart($itemTotal, $vendorId);
            $discount = $coupon ? $this->couponDiscount($coupon, $itemTotal) : 0.0;
            $grandTotal = max(0, $itemTotal - $discount) + $taxTotal + $deliveryCharge;

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $user->id,
                'vendor_id' => $vendorId,
                'total_amount' => $grandTotal,
                'delivery_charge' => $deliveryCharge,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'placed_at' => now(),
                'notes' => trim('Placed from storefront checkout. Delivery to zone '.$location['zone_id'].'. Tax applied: '.number_format($taxTotal, 2).($coupon ? '. Coupon applied: '.$coupon->code.' (-'.number_format($discount, 2).')' : '')),
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            foreach ($orderItems as $orderItem) {
                $order->items()->create($orderItem);
            }

            $order->load('items');
            app(InventoryService::class)->deductForOrder($order);
            app(OrderLifecycleService::class)->log($order, null, 'pending', $user->id, 'Order placed from storefront checkout.');

            $payment = Payment::create([
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

        $order->loadMissing(['items', 'customer', 'vendor']);

        try {
            event(new OrderPlaced($order));
        } catch (\Throwable $exception) {
            Log::error('Order placed event dispatch failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }

        session()->put('storefront.location', $location);
        session()->forget('storefront.soft_location');

        if ($request->string('payment_method')->toString() === 'online') {
            $this->clearCartState();
            return redirect()->route('payments.checkout', $order);
        }

        $this->clearCartState();

        return redirect()
            ->route('storefront.orders.success', $order)
            ->with('success', 'Order '.$order->order_number.' placed successfully. Payment is pending.');
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate([
            'coupon_code' => ['required', 'string', 'max:64'],
        ]);

        $itemTotal = (float) $this->cartItems()->sum('subtotal');
        $vendorId = $this->cartVendorId();

        if ($itemTotal <= 0 || ! $vendorId) {
            return back()->withErrors(['coupon_code' => 'Add items to your cart before applying a coupon.']);
        }

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($data['coupon_code']))])
            ->where('is_active', true)
            ->first();

        if (! $coupon || ($coupon->expires_at && now()->gt($coupon->expires_at))) {
            return back()->withErrors(['coupon_code' => 'Invalid or expired coupon']);
        }

        if ($coupon->min_order !== null && $itemTotal < (float) $coupon->min_order) {
            return back()->withErrors(['coupon_code' => 'Minimum order value not met']);
        }

        if ($coupon->vendor_id && (int) $coupon->vendor_id !== (int) $vendorId) {
            return back()->withErrors(['coupon_code' => 'Coupon not applicable for this vendor']);
        }

        session()->put('storefront.coupon', [
            'id' => $coupon->id,
            'code' => $coupon->code,
        ]);

        return back()->with('success', 'Coupon applied');
    }

    public function removeCoupon()
    {
        session()->forget('storefront.coupon');

        return back()->with('success', 'Coupon removed');
    }

    public function mergeGuestCart(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer', 403);

        if (! $request->has('items') && $request->has('guest_cart')) {
            $request->merge(['items' => $request->input('guest_cart')]);
        }

        if (is_string($request->input('items'))) {
            $decodedItems = json_decode($request->input('items'), true);
            $request->merge(['items' => is_array($decodedItems) ? $decodedItems : []]);
        }

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
            'cartTotals' => $this->cartTotals(),
        ]);
    }

    public function setLocation(Request $request)
    {
        $data = $request->validate([
            'country_id' => ['nullable', 'required_without:postcode', Rule::exists('countries', 'id')->where('status', 'active')],
            'city_id' => ['nullable', 'required_without:postcode', Rule::exists('cities', 'id')->where('status', 'active')],
            'zone_id' => ['nullable', 'exists:regions_zones,id'],
            'postcode' => ['nullable', 'string', 'max:32'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'force_clear' => ['nullable'],
        ]);

        if (! empty($data['postcode'])) {
            $resolvedLocation = $this->resolvePostcodeLocation($data['postcode']);

            if (! $resolvedLocation) {
                throw ValidationException::withMessages([
                    'postcode' => 'Delivery is not available in your area.',
                ]);
            }

            $data = array_merge($data, $resolvedLocation);
        }

        $resolvedFromVendorPostcode = ! empty($data['resolved_from_vendor_postcode']);

        $city = City::findOrFail($data['city_id']);

        if ((int) $city->country_id !== (int) $data['country_id']) {
            throw ValidationException::withMessages([
                'city_id' => 'Selected city must belong to the selected country.',
            ]);
        }

        $zone = null;

        if (! empty($data['zone_id'])) {
            $zone = RegionZone::query()
                ->where('id', $data['zone_id'])
                ->where('country_id', $data['country_id'])
                ->where('city_id', $data['city_id'])
                ->where('status', 'active')
                ->when(! $resolvedFromVendorPostcode, fn ($query) => $query->where('delivery_available', true))
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

        $resolvedPincode = ! empty($data['resolved_pincode']) ? $data['resolved_pincode'] : null;
        if ($this->vendorsForLocation($newLocation, $resolvedPincode)->isEmpty()) {
            throw ValidationException::withMessages([
                'location' => 'Delivery is not available in your area.',
            ]);
        }

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
        session()->put('storefront.address_prefill', [
            'address_line_1' => trim((string) ($data['address_line_1'] ?? '')) ?: trim(($zone?->zone_name ? $zone->zone_name.', ' : '').$city->city_name),
            'postcode' => $data['resolved_pincode'] ?? $data['postcode'] ?? null,
            'country_id' => (int) $data['country_id'],
            'city_id' => (int) $data['city_id'],
            'zone_id' => isset($data['zone_id']) ? (int) $data['zone_id'] : null,
        ]);
        session()->forget('storefront.soft_location');
        if (! empty($data['resolved_pincode'])) {
            session()->put('user_pincode', $data['resolved_pincode']);
        } else {
            session()->forget('user_pincode');
        }

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

    public function locationAutocomplete(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'keyword' => ['nullable', 'string', 'max:80'],
            ]);

            $keyword = trim((string) ($data['keyword'] ?? ''));

            if (mb_strlen($keyword) < 2) {
                return response()->json(['suggestions' => []]);
            }

            $like = "%{$keyword}%";
            $suggestions = collect();

            City::query()
                ->with('country')
                ->where('status', 'active')
                ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
                ->whereHas('vendors', fn ($vendorQuery) => $vendorQuery->where('status', 'active'))
                ->where(function ($query) use ($like) {
                    $query->where('city_name', 'like', $like)
                        ->orWhere('city_code', 'like', $like);
                })
                ->orderByRaw('CASE WHEN city_name LIKE ? THEN 0 ELSE 1 END', [$keyword.'%'])
                ->orderBy('city_name')
                ->limit(4)
                ->get()
                ->each(function (City $city) use ($suggestions) {
                    $suggestions->push([
                        'type' => 'city',
                        'label' => $city->city_name,
                        'meta' => $city->country?->country_name ?: 'City',
                        'country_id' => (int) $city->country_id,
                        'city_id' => (int) $city->id,
                        'zone_id' => null,
                        'postcode' => null,
                    ]);
                });

            RegionZone::query()
                ->with(['country', 'city'])
                ->where('status', 'active')
                ->where('delivery_available', true)
                ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
                ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
                ->where(function ($query) use ($like) {
                    $query->where('zone_name', 'like', $like)
                        ->orWhere('zone_code', 'like', $like);
                })
                ->orderByRaw('CASE WHEN zone_name LIKE ? THEN 0 ELSE 1 END', [$keyword.'%'])
                ->orderBy('zone_name')
                ->limit(4)
                ->get()
                ->each(function (RegionZone $zone) use ($suggestions) {
                    $suggestions->push([
                        'type' => 'zone',
                        'label' => $zone->zone_code ? "{$zone->zone_name} - {$zone->zone_code}" : $zone->zone_name,
                        'meta' => trim(($zone->city?->city_name ?: '').($zone->country?->country_name ? ', '.$zone->country->country_name : ''), ', '),
                        'country_id' => (int) $zone->country_id,
                        'city_id' => (int) $zone->city_id,
                        'zone_id' => (int) $zone->id,
                        'postcode' => null,
                    ]);
                });

            Vendor::query()
                ->with(['country', 'city', 'zone'])
                ->where('status', 'active')
                ->whereNotNull('pincode')
                ->where('pincode', 'like', $like)
                ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
                ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
                ->orderByRaw('CASE WHEN pincode LIKE ? THEN 0 ELSE 1 END', [$keyword.'%'])
                ->orderBy('pincode')
                ->limit(5)
                ->get()
                ->unique(fn (Vendor $vendor) => $this->normalizedPostcode((string) $vendor->pincode))
                ->each(function (Vendor $vendor) use ($suggestions) {
                    $postcode = mb_strtoupper(trim((string) $vendor->pincode));
                    $suggestions->push([
                        'type' => 'pincode',
                        'label' => $postcode,
                        'meta' => trim(($vendor->city?->city_name ?: '').($vendor->country?->country_name ? ', '.$vendor->country->country_name : ''), ', '),
                        'country_id' => (int) $vendor->country_id,
                        'city_id' => (int) $vendor->city_id,
                        'zone_id' => $vendor->region_zone_id ? (int) $vendor->region_zone_id : null,
                        'postcode' => $postcode,
                    ]);
                });

            Country::query()
                ->where('status', 'active')
                ->where('country_name', 'like', $like)
                ->orderByRaw('CASE WHEN country_name LIKE ? THEN 0 ELSE 1 END', [$keyword.'%'])
                ->orderBy('country_name')
                ->limit(3)
                ->get()
                ->each(function (Country $country) use ($suggestions) {
                    $city = City::query()
                        ->where('country_id', $country->id)
                        ->where('status', 'active')
                        ->whereHas('vendors', fn ($vendorQuery) => $vendorQuery->where('status', 'active'))
                        ->orderBy('city_name')
                        ->first();

                    if (! $city) {
                        return;
                    }

                    $suggestions->push([
                        'type' => 'country',
                        'label' => $country->country_name,
                        'meta' => 'Country',
                        'country_id' => (int) $country->id,
                        'city_id' => (int) $city->id,
                        'zone_id' => null,
                        'postcode' => null,
                    ]);
                });

            Vendor::query()
                ->with(['country', 'city', 'zone'])
                ->where('status', 'active')
                ->whereNotNull('address')
                ->where('address', 'like', $like)
                ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
                ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
                ->orderBy('vendor_name')
                ->limit(3)
                ->get()
                ->each(function (Vendor $vendor) use ($suggestions) {
                    $address = Str::limit(trim((string) $vendor->address), 48, '');
                    $suggestions->push([
                        'type' => 'area',
                        'label' => $address,
                        'meta' => trim(($vendor->city?->city_name ?: '').($vendor->pincode ? ' - '.$vendor->pincode : ''), ' -'),
                        'country_id' => (int) $vendor->country_id,
                        'city_id' => (int) $vendor->city_id,
                        'zone_id' => $vendor->region_zone_id ? (int) $vendor->region_zone_id : null,
                        'postcode' => $vendor->pincode ? mb_strtoupper(trim((string) $vendor->pincode)) : null,
                    ]);
                });

            $deduped = $suggestions
                ->filter(fn (array $item) => ! empty($item['label']) && ! empty($item['country_id']) && ! empty($item['city_id']))
                ->unique(fn (array $item) => implode(':', [
                    $item['type'],
                    mb_strtolower((string) $item['label']),
                    $item['country_id'],
                    $item['city_id'],
                    $item['zone_id'] ?? '',
                    $item['postcode'] ?? '',
                ]))
                ->take(10)
                ->values();

            return response()->json(['suggestions' => $deduped]);
        } catch (Throwable $exception) {
            return $this->apiError($exception);
        }
    }

    public function cities(Request $request): JsonResponse
    {
        try {
            $cities = City::query()
                ->where('status', 'active')
                ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
                ->when($request->filled('country_id'), function ($query) use ($request) {
                    $query->where('country_id', $request->integer('country_id'));
                })
                ->orderBy('city_name')
                ->get(['id', 'city_name', 'country_id']);

            return response()->json(['cities' => $cities]);
        } catch (Throwable $exception) {
            return $this->apiError($exception);
        }
    }

    public function zones(Request $request): JsonResponse
    {
        try {
            $zones = RegionZone::query()
                ->where('status', 'active')
                ->where('delivery_available', true)
                ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
                ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
                ->when($request->filled('city_id'), function ($query) use ($request) {
                    $query->where('city_id', $request->integer('city_id'));
                })
                ->orderBy('zone_name')
                ->get(['id', 'zone_name', 'zone_code', 'city_id']);

            return response()->json(['zones' => $zones]);
        } catch (Throwable $exception) {
            return $this->apiError($exception);
        }
    }

    public function vendorsByLocation(): JsonResponse
    {
        try {
            $location = $this->browsingLocation();

            if (! $location) {
                return response()->json([]);
            }

            $vendors = $this->vendorsForLocation($location, $this->activePincode())
                ->map(fn (Vendor $vendor) => [
                    'id' => $vendor->id,
                    'name' => $vendor->vendor_name,
                ])
                ->values();

            return response()->json($vendors);
        } catch (Throwable $exception) {
            return $this->apiError($exception);
        }
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
        $requestedQuantity = $currentQuantity + $quantity;

        $product->loadMissing('inventory');
        if ($product->inventory?->inventory_mode === 'internal' && (int) $product->inventory->stock_quantity < $requestedQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Not enough stock available',
                'cartCount' => $this->cartCount(),
                'drawerHtml' => $this->renderCartDrawer(),
                'cartState' => $this->cartState(),
                'cartTotals' => $this->cartTotals(),
            ], 422);
        }

        $cart[$product->id] = [
            'quantity' => $requestedQuantity,
        ];

        session()->put('storefront.cart', $cart);
        session()->put('storefront.cart_vendor_id', $productVendorId);

        return response()->json([
            'success' => true,
            'message' => 'Added to cart',
            'cartCount' => $this->cartCount(),
            'drawerHtml' => $this->renderCartDrawer(),
            'cartState' => $this->cartState(),
            'cartTotals' => $this->cartTotals(),
            'cartItem' => [
                'productId' => $product->id,
                'quantity' => $requestedQuantity,
                'unitPrice' => (float) ($product->final_price ?: $product->price),
                'subtotal' => (float) ($product->final_price ?: $product->price) * $requestedQuantity,
            ],
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
            $product->loadMissing('inventory');

            if ($product->inventory?->inventory_mode === 'internal' && (int) $product->inventory->stock_quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not enough stock available',
                    'cartCount' => $this->cartCount(),
                    'drawerHtml' => $this->renderCartDrawer(),
                    'cartState' => $this->cartState(),
                    'cartTotals' => $this->cartTotals(),
                ], 422);
            }

            $cart[$product->id]['quantity'] = $newQuantity;
        }

        session()->put('storefront.cart', $cart);

        if (empty($cart)) {
            session()->forget('storefront.cart_vendor_id');
        }

        $cartItems = $this->cartItems();
        $cartItem = $cartItems->first(fn ($item) => (int) $item['product']->id === (int) $product->id);

        return response()->json([
            'success' => true,
            'cartCount' => $this->cartCount(),
            'drawerHtml' => $this->renderCartDrawer(),
            'cartState' => $this->cartState(),
            'cartTotals' => $this->cartTotals(),
            'cartItem' => $cartItem ? [
                'productId' => $product->id,
                'quantity' => $cartItem['quantity'],
                'unitPrice' => $cartItem['unit_price'],
                'subtotal' => $cartItem['subtotal'],
            ] : null,
            'removedProductId' => $cartItem ? null : $product->id,
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
            'cartTotals' => $this->cartTotals(),
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
            'cartTotals' => $this->cartTotals(),
        ]);
    }

    private function storefrontData(Request $request, string $title, array $extra = []): array
    {
        $location = $this->browsingLocation();
        $pincode = $this->activePincode();
        $selectedVendorId = $request->filled('vendor_id') ? $request->integer('vendor_id') : null;
        $featuredSections = $this->featuredSections($location);
        $selectedVendor = $selectedVendorId ? $this->activeVendor($selectedVendorId, $location, $pincode) : null;
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
            'pincode' => $pincode,
            'selectedVendorId' => $selectedVendorId,
            'selectedVendor' => $selectedVendor,
            'selectedVendorProducts' => $selectedVendor ? $this->vendorProducts($selectedVendor, $location) : collect(),
            'vendors' => $this->vendorsForLocation($location, $pincode),
            'hasPincodeProducts' => ! $pincode || $this->productsQuery($location)->exists(),
            'cartCount' => $this->cartCount(),
            'cartItems' => $cartItems,
            'cartMap' => $cartItems->keyBy(fn ($item) => $item['product']->id),
            'cartState' => $this->cartState(),
            'cartTotals' => $this->cartTotals(),
            'categories' => $categories,
            'featuredSections' => $featuredSections,
            'discountedProducts' => $this->discountedProducts($location),
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
        $pincode = $this->activePincode();

        $subcategories = Subcategory::query()
            ->where('status', 'active')
            ->with(['category', 'products' => function ($query) use ($location, $pincode) {
                $this->applyProductScope($query, $location);
                $this->applyPincodeScope($query, $pincode);
                $this->applySelectedVendorScope($query);
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
                'subcategory' => null,
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
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery->where('product_name', 'like', "%{$keyword}%")
                        ->orWhereHas('category', function ($categoryQuery) use ($keyword) {
                            $categoryQuery->where('status', 'active')
                                ->where('category_name', 'like', "%{$keyword}%");
                        })
                        ->orWhereHas('subcategory', function ($subcategoryQuery) use ($keyword) {
                            $subcategoryQuery->where('status', 'active')
                                ->where('subcategory_name', 'like', "%{$keyword}%");
                        });
                })
                    ->orderByRaw(
                        'CASE WHEN product_name LIKE ? THEN 1 ELSE 2 END',
                        ["%{$keyword}%"]
                    )
                    ->orderBy('product_name');
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

    private function discountedProducts(?array $location)
    {
        return $this->productsQuery($location)
            ->whereNotNull('discount_type')
            ->whereNotNull('discount_value')
            ->whereColumn('final_price', '<', 'price')
            ->orderBy('discount_value', 'desc')
            ->limit(12)
            ->get();
    }

    private function categoryProducts(Category $category, ?Subcategory $subcategory = null)
    {
        $query = $this->productsQuery($this->browsingLocation())
            ->where('category_id', $category->id);

        if ($subcategory) {
            $query->where('subcategory_id', $subcategory->id);
        }

        return $query->limit(60)->get();
    }

    private function subcategoryProducts(Subcategory $subcategory)
    {
        return $this->productsQuery($this->browsingLocation())
            ->where('subcategory_id', $subcategory->id)
            ->limit(60)
            ->get();
    }

    private function vendorProducts(Vendor $vendor, ?array $location)
    {
        return $this->productsQuery($location)
            ->where('vendor_id', $vendor->id)
            ->limit(12)
            ->get();
    }

    private function productsQuery(?array $location)
    {
        $query = Product::query()
            ->with(['category', 'subcategory', 'vendor', 'images', 'inventory', 'tax'])
            ->where('status', 'active');

        $this->applyProductScope($query, $location);
        $this->applyPincodeScope($query, $this->activePincode());
        $this->applySelectedVendorScope($query);

        if (request('sort') === 'price_low') {
            $query->orderByRaw('COALESCE(final_price, price) asc');
        }

        if (request('sort') === 'price_high') {
            $query->orderByRaw('COALESCE(final_price, price) desc');
        }

        return $query;
    }

    private function applySelectedVendorScope($query): void
    {
        if (! request()->filled('vendor_id')) {
            return;
        }

        $vendorId = request()->integer('vendor_id');

        if ($vendorId > 0) {
            $query->where('vendor_id', $vendorId);
        }
    }

    private function applyProductScope($query, ?array $location): void
    {
        $query->whereHas('vendor', function ($vendorQuery) use ($location) {
            $vendorQuery->where('status', 'active');
            $vendorQuery->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'));
            $vendorQuery->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'));
            $vendorQuery->where(function ($zoneStatusQuery) {
                $zoneStatusQuery->whereNull('region_zone_id')
                    ->orWhereHas('zone', fn ($zoneQuery) => $zoneQuery->where('status', 'active'));
            });
            $this->applyVendorLocationScope($vendorQuery, $location);
        });

        $query->where(function ($stockQuery) {
            $stockQuery->whereHas('inventory', function ($inventoryQuery) {
                $inventoryQuery->where('inventory_mode', 'epos');
            })->orWhereHas('inventory', function ($inventoryQuery) {
                $inventoryQuery->where('inventory_mode', 'internal')->where('stock_quantity', '>', 0);
            });
        });
    }

    private function applyPincodeScope($query, ?string $pincode): void
    {
        if (! $pincode) {
            return;
        }

        $vendorIds = $this->vendorIdsForPincode($pincode);

        if ($vendorIds->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('vendor_id', $vendorIds->all());
    }

    private function activePincode(): ?string
    {
        $pincode = trim((string) request()->input('pincode', request()->input('postcode', session('user_pincode', ''))));

        return $pincode !== '' ? mb_strtoupper($pincode) : null;
    }

    private function rememberRecentSearch(string $keyword): void
    {
        $recent = collect(session('recent_searches', []))
            ->reject(fn ($item) => mb_strtolower((string) $item) === mb_strtolower($keyword))
            ->push($keyword)
            ->take(-5)
            ->values()
            ->all();

        session()->put('recent_searches', $recent);
    }

    private function recentSearches(): array
    {
        return array_slice(session('recent_searches', []), -5);
    }

    private function productGridResponse($products, string $emptyMessage, array $extra = []): JsonResponse
    {
        $cartItems = $this->cartItems();

        return response()->json(array_merge([
            'html' => View::make('storefront.partials.product-grid', [
                'products' => $products,
                'emptyMessage' => $emptyMessage,
                'cartMap' => $cartItems->keyBy(fn ($item) => $item['product']->id),
                'pincode' => $this->activePincode(),
                'selectedVendorId' => request()->filled('vendor_id') ? request()->integer('vendor_id') : null,
            ])->render(),
        ], $extra));
    }

    private function apiError(Throwable $exception): JsonResponse
    {
        Log::error('Storefront API request failed.', [
            'error' => $exception->getMessage(),
        ]);

        return response()->json([
            'error' => true,
            'message' => config('ui_messages.api_error'),
        ], 500);
    }

    private function vendorIdsForPincode(string $pincode)
    {
        static $cache = [];
        $normalizedPincode = $this->normalizedPostcode($pincode);

        if (isset($cache[$normalizedPincode])) {
            return $cache[$normalizedPincode];
        }

        $cache[$normalizedPincode] = Vendor::query()
            ->where('status', 'active')
            ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
            ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
            ->where(function ($zoneStatusQuery) {
                $zoneStatusQuery->whereNull('region_zone_id')
                    ->orWhereHas('zone', fn ($zoneQuery) => $zoneQuery->where('status', 'active'));
            })
            ->whereRaw("UPPER(REPLACE(REPLACE(TRIM(COALESCE(pincode, '')), ' ', ''), '-', '')) = ?", [$normalizedPincode])
            ->pluck('id');

        return $cache[$normalizedPincode];
    }

    private function resolvePostcodeLocation(string $postcode): ?array
    {
        $postcode = mb_strtoupper(trim($postcode));
        $normalizedPostcode = $this->normalizedPostcode($postcode);

        if ($normalizedPostcode === '') {
            return null;
        }

        $vendor = Vendor::query()
            ->where('status', 'active')
            ->whereRaw("UPPER(REPLACE(REPLACE(TRIM(COALESCE(pincode, '')), ' ', ''), '-', '')) = ?", [$normalizedPostcode])
            ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
            ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
            ->where(function ($zoneStatusQuery) {
                $zoneStatusQuery->whereNull('region_zone_id')
                    ->orWhereHas('zone', fn ($zoneQuery) => $zoneQuery->where('status', 'active'));
            })
            ->orderBy('vendor_name')
            ->first();

        if ($vendor) {
            return [
                'country_id' => (int) $vendor->country_id,
                'city_id' => (int) $vendor->city_id,
                'zone_id' => null,
                'resolved_pincode' => $normalizedPostcode,
                'resolved_from_vendor_postcode' => true,
            ];
        }

        $zone = RegionZone::query()
            ->whereRaw('LOWER(zone_code) = ?', [mb_strtolower($postcode)])
            ->where('status', 'active')
            ->where('delivery_available', true)
            ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
            ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
            ->first();

        if (! $zone) {
            return null;
        }

        return [
            'country_id' => (int) $zone->country_id,
            'city_id' => (int) $zone->city_id,
            'zone_id' => (int) $zone->id,
            'resolved_pincode' => null,
            'resolved_from_vendor_postcode' => false,
        ];
    }

    private function normalizedPostcode(string $postcode): string
    {
        return mb_strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', trim($postcode)));
    }

    private function vendorsForLocation(?array $location, ?string $pincode)
    {
        $query = Vendor::query()
            ->where('status', 'active')
            ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
            ->whereHas('city', fn ($cityQuery) => $cityQuery->where('status', 'active'))
            ->where(function ($zoneStatusQuery) {
                $zoneStatusQuery->whereNull('region_zone_id')
                    ->orWhereHas('zone', fn ($zoneQuery) => $zoneQuery->where('status', 'active'));
            });

        $this->applyVendorLocationScope($query, $location);

        if ($pincode) {
            $query->whereRaw("UPPER(REPLACE(REPLACE(TRIM(COALESCE(pincode, '')), ' ', ''), '-', '')) = ?", [$this->normalizedPostcode($pincode)]);
        }

        return $query->orderBy('vendor_name')->get(['id', 'vendor_name', 'pincode']);
    }

    private function activeVendor(int $vendorId, ?array $location, ?string $pincode): ?Vendor
    {
        return $this->vendorsForLocation($location, $pincode)
            ->firstWhere('id', $vendorId);
    }

    private function applyVendorLocationScope($query, ?array $location): void
    {
        if (! $location) {
            return;
        }

        if (! empty($location['country_id'])) {
            $query->where('country_id', $location['country_id']);
        }

        if (! empty($location['city_id'])) {
            $query->where('city_id', $location['city_id']);
        }

        if (! empty($location['zone_id'])) {
            $query->where('region_zone_id', $location['zone_id']);
        }
    }

    private function vendorMatchesLocation(?Vendor $vendor, ?array $location): bool
    {
        if (! $vendor || ! $location) {
            return false;
        }

        if (! empty($location['country_id']) && (int) $vendor->country_id !== (int) $location['country_id']) {
            return false;
        }

        if (! empty($location['city_id']) && (int) $vendor->city_id !== (int) $location['city_id']) {
            return false;
        }

        if (! empty($location['zone_id']) && (int) $vendor->region_zone_id !== (int) $location['zone_id']) {
            return false;
        }

        return true;
    }

    private function hardLocation(): ?array
    {
        $location = session('storefront.location');

        if (! is_array($location)) {
            return null;
        }

        $location = $this->activeLocationOrNull($location);

        if (! $location) {
            session()->forget(['storefront.location', 'storefront.soft_location', 'user_pincode']);
        }

        return $location;
    }

    private function softLocation(): ?array
    {
        $stored = session('storefront.soft_location');
        if (is_array($stored)) {
            $location = $this->activeLocationOrNull($stored);

            if ($location) {
                return array_merge($location, ['mode' => 'soft']);
            }

            session()->forget('storefront.soft_location');
        }

        $city = $this->defaultCityFromIp();

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

    private function activeLocationOrNull(array $location): ?array
    {
        if (empty($location['country_id']) || empty($location['city_id'])) {
            return null;
        }

        $city = City::query()
            ->whereKey($location['city_id'])
            ->where('country_id', $location['country_id'])
            ->where('status', 'active')
            ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
            ->first();

        if (! $city) {
            return null;
        }

        $zoneId = ! empty($location['zone_id']) ? (int) $location['zone_id'] : null;

        if ($zoneId) {
            $zoneExists = RegionZone::query()
                ->whereKey($zoneId)
                ->where('country_id', $location['country_id'])
                ->where('city_id', $location['city_id'])
                ->where('status', 'active')
                ->where('delivery_available', true)
                ->exists();

            if (! $zoneExists) {
                return null;
            }
        }

        return [
            'country_id' => (int) $location['country_id'],
            'city_id' => (int) $location['city_id'],
            'zone_id' => $zoneId,
        ];
    }

    private function defaultCityFromIp(): ?City
    {
        $request = request();
        $ip = $request?->ip();
        $countryCode = strtoupper((string) ($request?->header('CF-IPCountry')
            ?? $request?->header('X-AppEngine-Country')
            ?? $request?->header('X-Vercel-IP-Country')
            ?? ''));

        $preferredCityCode = match ($countryCode) {
            'GB', 'UK' => 'SOU',
            'IN' => 'MUM',
            default => null,
        };

        if ($this->isLocalIp($ip)) {
            $preferredCityCode = 'SOU';
        }

        if ($preferredCityCode) {
            $city = City::query()
                ->where('status', 'active')
                ->where('city_code', $preferredCityCode)
                ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
                ->with('country')
                ->first();

            if ($city) {
                return $city;
            }
        }

        return City::query()
            ->where('status', 'active')
            ->whereHas('country', fn ($countryQuery) => $countryQuery->where('status', 'active'))
            ->with('country')
            ->orderBy('city_name')
            ->first();
    }

    private function isLocalIp(?string $ip): bool
    {
        if (! $ip) {
            return true;
        }

        return $ip === '127.0.0.1'
            || $ip === '::1'
            || str_starts_with($ip, '10.')
            || str_starts_with($ip, '192.168.')
            || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) === 1;
    }

    private function browsingLocation(): ?array
    {
        return $this->hardLocation();
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
            ->with(['images', 'vendor', 'inventory', 'tax'])
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
            $subtotal = $unitPrice * $quantity;
            $taxAmount = $this->cartItemTax($product, $subtotal);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
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
        $taxTotal = $items->sum('tax_amount');
        $delivery = $deliveryCharge ?? $this->cartDeliveryCharge();
        $vendorId = $this->cartVendorId();
        $coupon = $this->validCouponForCart((float) $itemTotal, $vendorId);
        $discount = $coupon ? $this->couponDiscount($coupon, (float) $itemTotal) : 0.0;

        return [
            'itemTotal' => $itemTotal,
            'tax' => $taxTotal,
            'delivery' => $delivery,
            'discount' => $discount,
            'grandTotal' => max(0, $itemTotal - $discount) + $taxTotal + $delivery,
            'coupon' => $coupon ? [
                'code' => $coupon->code,
                'type' => $coupon->type,
                'value' => (float) $coupon->value,
            ] : null,
        ];
    }

    private function cartDeliveryCharge(): float
    {
        $user = request()?->user();

        if (! $user || $user->role !== 'customer') {
            return 0.0;
        }

        $address = CustomerAddress::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->orderByDesc('is_default')
            ->latest()
            ->first();

        if (! $address?->zone_id) {
            return 0.0;
        }

        return $this->deliveryChargeForZone($address->zone_id) ?? 0.0;
    }

    private function validCouponForCart(float $itemTotal, ?int $vendorId): ?Coupon
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

    private function cartItemTax(Product $product, float $subtotal): float
    {
        if (! $product->tax || $product->tax->status !== 'active') {
            return 0.0;
        }

        return round($subtotal * ((float) $product->tax->tax_percentage / 100), 2);
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
        $location = $this->hardLocation();

        if (! $location) {
            throw ValidationException::withMessages([
                'location' => 'Please enter your delivery location to add items.',
            ]);
        }

        $product->loadMissing(['vendor', 'inventory']);

        if ($product->vendor?->status !== 'active') {
            throw ValidationException::withMessages(['product' => 'Vendor is currently unavailable.']);
        }

        if (! $this->vendorMatchesLocation($product->vendor, $location)) {
            throw ValidationException::withMessages([
                'location' => 'This product is not available for your selected location.',
            ]);
        }

        if ($product->inventory?->inventory_mode === 'internal' && (int) $product->inventory->stock_quantity <= 0) {
            throw ValidationException::withMessages(['product' => 'Product is out of stock.']);
        }
    }

    private function isStorefrontProductAvailable(Product $product): bool
    {
        if ($product->status !== 'active' || $product->vendor?->status !== 'active') {
            return false;
        }

        if ($product->vendor?->country?->status !== 'active' || $product->vendor?->city?->status !== 'active') {
            return false;
        }

        if ($product->vendor?->zone && $product->vendor->zone->status !== 'active') {
            return false;
        }

        if ($product->inventory?->inventory_mode === 'internal' && (int) $product->inventory->stock_quantity <= 0) {
            return false;
        }

        return true;
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
        session()->forget('storefront.coupon');
    }
}
