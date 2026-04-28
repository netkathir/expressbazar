<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductInventory;
use App\Services\InventoryService;
use App\Services\OrderLifecycleService;
use App\Models\RegionZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer', 403);

        return view('storefront.account.index', [
            'title' => 'My Account',
            'user' => $user,
            'addresses' => $user->addresses()->with(['country', 'city', 'zone'])->latest()->get(),
            'orders' => Order::query()
                ->where('customer_id', $user->id)
                ->with(['vendor', 'items', 'payments'])
                ->latest('placed_at')
                ->latest('id')
                ->limit(5)
                ->get(),
        ]);
    }

    public function editProfile(Request $request)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer', 403);

        return view('storefront.account.profile-edit', [
            'title' => 'Edit Profile',
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()->route('storefront.account')->with('success', 'Profile updated successfully.');
    }

    public function orders(Request $request)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer', 403);

        $orders = Order::query()
            ->where('customer_id', $user->id)
            ->with(['vendor', 'items', 'payments'])
            ->latest('placed_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('storefront.orders.index', [
            'title' => 'My Orders',
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function showOrder(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        return view('storefront.orders.show', [
            'title' => 'Order Details',
            'order' => $order->load(['vendor', 'items.product.vendor', 'items.product.inventory', 'payments']),
            'user' => $user,
        ]);
    }

    public function orderStatus(Request $request, Order $order): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        return response()->json([
            'status' => $order->order_status,
            'label' => ucfirst($order->order_status),
        ]);
    }

    public function cancelOrder(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        DB::transaction(function () use ($order, $user) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->with('items')
                ->firstOrFail();

            if (! $this->orderCanBeCancelled($lockedOrder->order_status)) {
                throw ValidationException::withMessages([
                    'order' => 'Order cannot be cancelled at this stage.',
                ]);
            }

            app(OrderLifecycleService::class)->transition($lockedOrder, 'cancelled', $user->id, 'Customer cancelled order.');
            if (Schema::hasColumn('orders', 'status')) {
                $lockedOrder->update(['status' => 'cancelled']);
            }
            $this->syncCancelledTrackingSteps($lockedOrder);
            app(InventoryService::class)->restoreForCancelledOrder($lockedOrder);
        });

        return back()->with('success', 'Order cancelled successfully.');
    }

    public function reorder(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        $order->load(['items.product.vendor', 'items.product.inventory']);

        if ($order->items->isEmpty()) {
            return back()->withErrors(['order' => 'This order has no items to reorder.']);
        }

        $currentVendorId = $this->cartVendorId();
        $targetVendorId = null;
        $pincode = session('user_pincode');
        $cart = session()->get('storefront.cart', []);

        foreach ($order->items as $item) {
            $product = $item->product;

            if (! $product || $product->status !== 'active') {
                return back()->withErrors(['order' => 'Some items are not available for reorder.']);
            }

            if (! $product->vendor || $product->vendor->status !== 'active') {
                return back()->withErrors(['order' => 'Some items are not available for reorder.']);
            }

            if ($pincode && $product->vendor->pincode && mb_strtoupper($product->vendor->pincode) !== mb_strtoupper($pincode)) {
                return back()->withErrors(['order' => 'Some items are not available for reorder in your area.']);
            }

            $productVendorId = (int) $product->vendor_id;
            $targetVendorId ??= $productVendorId;

            if ($targetVendorId !== $productVendorId || ($currentVendorId && $currentVendorId !== $productVendorId)) {
                return back()->withErrors(['order' => 'Your cart contains items from another vendor. Please clear cart to continue.']);
            }

            $requestedQuantity = (int) ($cart[$product->id]['quantity'] ?? 0) + (int) $item->quantity;

            if ($product->inventory?->inventory_mode === 'internal' && (int) $product->inventory->stock_quantity < $requestedQuantity) {
                return back()->withErrors(['order' => 'Some items are not available in the requested quantity.']);
            }
        }

        foreach ($order->items as $item) {
            $productId = (int) $item->product_id;
            $cart[$productId] = [
                'quantity' => (int) ($cart[$productId]['quantity'] ?? 0) + (int) $item->quantity,
            ];
        }

        session()->put('storefront.cart', $cart);
        if ($targetVendorId) {
            session()->put('storefront.cart_vendor_id', $targetVendorId);
        }

        return redirect()->route('storefront.cart')->with('success', 'Items added to cart.');
    }

    public function showOrderSuccess(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        return view('storefront.orders.success', [
            'title' => 'Order Confirmed',
            'order' => $order->load(['vendor', 'items', 'payments']),
            'user' => $user,
        ]);
    }

    public function cancelPayment(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        $payment = $order->payments()->latest()->first();
        if ($payment && $payment->payment_method === 'online' && in_array($payment->status, ['pending', 'failed'], true)) {
            $payment->update([
                'status' => 'failed',
                'gateway_response' => json_encode([
                    'source' => 'stripe_checkout',
                    'status' => 'cancelled',
                    'requested_at' => now()->toDateTimeString(),
                ]),
            ]);
        }

        $order->update([
            'payment_status' => 'failed',
            'updated_by' => $user->id,
        ]);

        return redirect()
            ->route('storefront.orders.show', $order)
            ->with('error', 'Stripe payment was cancelled. You can retry it from your order details.');
    }

    public function retryPayment(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        $latestPayment = $order->payments()->latest()->first();
        if (! $latestPayment || $latestPayment->payment_method !== 'online') {
            return back()->withErrors(['payment' => 'Online payment retry is not available for this order.']);
        }

        $latestPayment->update([
            'status' => 'failed',
            'gateway_response' => json_encode([
                'source' => 'stripe_checkout',
                'status' => 'retry_initiated',
                'requested_at' => now()->toDateTimeString(),
            ]),
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'transaction_id' => $this->generateTransactionId(),
            'payment_method' => 'online',
            'amount' => (float) $order->total_amount,
            'status' => 'pending',
            'gateway_response' => json_encode([
                'source' => 'stripe_checkout',
                'status' => 'retry_pending',
                'order_number' => $order->order_number,
            ]),
        ]);

        $order->update([
            'payment_status' => 'pending',
            'updated_by' => $user->id,
        ]);

        $order->loadMissing(['items', 'customer']);

        return redirect()->route('payments.checkout', $order);
    }

    public function storeAddress(Request $request)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer', 403);

        $data = $this->validateAddressPayload($request);

        if ($request->boolean('is_default')) {
            $user->addresses()->update(['is_default' => false]);
        }

        CustomerAddress::create([
            'user_id' => $user->id,
            'label' => $data['label'] ?? null,
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'] ?? null,
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'country_id' => $data['country_id'],
            'city_id' => $data['city_id'],
            'zone_id' => $data['zone_id'] ?? null,
            'postcode' => $data['postcode'],
            'is_default' => $request->boolean('is_default'),
            'status' => 'active',
        ]);

        return redirect()->route('storefront.account')->with('success', 'Address saved successfully.');
    }

    public function editAddress(Request $request, CustomerAddress $address)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer' || (int) $address->user_id !== (int) $user->id, 403);

        return view('storefront.account.address-edit', [
            'title' => 'Edit Address',
            'user' => $user,
            'address' => $address->load(['country', 'city', 'zone']),
            'countries' => Country::query()->where('status', 'active')->orderBy('country_name')->get(),
            'cities' => City::query()
                ->where('country_id', $address->country_id)
                ->orderBy('city_name')
                ->get(),
            'zones' => RegionZone::query()
                ->where('country_id', $address->country_id)
                ->where('city_id', $address->city_id)
                ->where('status', 'active')
                ->where('delivery_available', true)
                ->orderBy('zone_name')
                ->get(),
        ]);
    }

    public function updateAddress(Request $request, CustomerAddress $address)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer' || (int) $address->user_id !== (int) $user->id, 403);

        $data = $this->validateAddressPayload($request);

        if ($request->boolean('is_default')) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address->update([
            'label' => $data['label'] ?? null,
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'] ?? null,
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'country_id' => $data['country_id'],
            'city_id' => $data['city_id'],
            'zone_id' => $data['zone_id'] ?? null,
            'postcode' => $data['postcode'],
            'is_default' => $request->boolean('is_default'),
        ]);

        return redirect()->route('storefront.account')->with('success', 'Address updated successfully.');
    }

    public function destroyAddress(Request $request, CustomerAddress $address)
    {
        abort_if($address->user_id !== $request->user()?->id, 403);

        $address->delete();

        return back()->with('success', 'Address removed.');
    }

    private function generateTransactionId(): string
    {
        do {
            $transactionId = 'PAY-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        } while (Payment::query()->where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    private function orderCanBeCancelled(?string $status): bool
    {
        return ! in_array(mb_strtolower((string) $status), ['dispatched', 'delivered', 'completed', 'cancelled'], true);
    }

    private function syncCancelledTrackingSteps(Order $order): void
    {
        if (! Schema::hasTable('order_trackings') || ! Schema::hasColumn('order_trackings', 'order_id') || ! Schema::hasColumn('order_trackings', 'status')) {
            return;
        }

        $updates = ['status' => 'cancelled'];

        if (Schema::hasColumn('order_trackings', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('order_trackings')
            ->where('order_id', $order->id)
            ->whereIn('status', ['pending', 'incomplete', 'PENDING', 'INCOMPLETE'])
            ->update($updates);
    }

    private function cartVendorId(): ?int
    {
        $vendorId = session('storefront.cart_vendor_id');

        return $vendorId ? (int) $vendorId : null;
    }

    private function validateAddressPayload(Request $request): array
    {
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'zone_id' => ['nullable', 'exists:regions_zones,id'],
            'postcode' => ['required', 'string', 'max:32'],
            'is_default' => ['nullable'],
        ]);

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

        return $data;
    }
}
