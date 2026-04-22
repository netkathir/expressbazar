<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RegionZone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
            'order' => $order->load(['vendor', 'items', 'payments']),
            'user' => $user,
        ]);
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

    public function retryPayment(Request $request, Order $order)
    {
        $user = $request->user();
        abort_if(! $user || $user->role !== 'customer' || (int) $order->customer_id !== (int) $user->id, 403);

        $latestPayment = $order->payments()->latest()->first();
        if (! $latestPayment || $latestPayment->payment_method !== 'online') {
            return back()->withErrors(['payment' => 'Online payment retry is not available for this order.']);
        }

        if (in_array($latestPayment->status, ['pending', 'failed'], true)) {
            $latestPayment->update([
                'status' => 'failed',
                'gateway_response' => json_encode([
                    'status' => 'retry_initiated',
                    'requested_at' => now()->toDateTimeString(),
                ]),
            ]);
        }

        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => $this->generateTransactionId(),
            'payment_method' => 'online',
            'amount' => (float) $order->total_amount,
            'status' => 'pending',
            'gateway_response' => json_encode([
                'status' => 'retry_pending',
                'order_number' => $order->order_number,
            ]),
        ]);

        $order->update([
            'payment_status' => 'pending',
            'updated_by' => $user->id,
        ]);

        return redirect()->route('storefront.orders.show', $order)->with('success', 'Payment retry initiated.');
    }

    public function storeAddress(Request $request)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer', 403);

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
            return back()->withErrors(['city_id' => 'Selected city must belong to the selected country.'])->withInput();
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
                return back()->withErrors(['zone_id' => 'Delivery is not available in your area.'])->withInput();
            }
        }

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
}
