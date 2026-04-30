<?php

namespace App\Http\Controllers\Vendor;

use App\Events\TriggerNotificationEvent;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\VendorOrderWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['customer', 'vendor'])
            ->where('vendor_id', Auth::guard('vendor')->id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where('order_number', 'like', "%{$search}%");
            })
            ->when($request->filled('payment_status'), fn ($query) => $query->where('payment_status', $request->string('payment_status')))
            ->when($request->filled('order_status'), fn ($query) => $query->where('order_status', $request->string('order_status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', [
            'title' => 'Order Management',
            'activeMenu' => 'orders',
            'orders' => $orders,
            'routePrefix' => 'vendor.orders',
            'isVendorPanel' => true,
        ]);
    }

    public function show(Order $order)
    {
        $this->authorizeVendorOrder($order);

        return view('admin.orders.show', [
            'title' => 'Order Details',
            'activeMenu' => 'orders',
            'order' => $order->load(['customer', 'vendor', 'items']),
            'routePrefix' => 'vendor.orders',
            'isVendorPanel' => true,
        ]);
    }

    public function accept(Order $order)
    {
        $this->transitionVendorOrder($order, 'accepted');

        return redirect()->route('vendor.orders.show', $order)->with('success', 'Order accepted successfully.');
    }

    public function reject(Request $request, Order $order)
    {
        $this->transitionVendorOrder($order, 'cancelled', $request->string('reason')->toString());

        return redirect()->route('vendor.orders.show', $order)->with('success', 'Order rejected successfully.');
    }

    public function processing(Order $order)
    {
        $this->transitionVendorOrder($order, 'processing');

        return redirect()->route('vendor.orders.show', $order)->with('success', 'Order moved to processing.');
    }

    public function dispatched(Order $order)
    {
        $this->transitionVendorOrder($order, 'dispatched');

        return redirect()->route('vendor.orders.show', $order)->with('success', 'Order marked as dispatched.');
    }

    public function delivered(Order $order)
    {
        $this->transitionVendorOrder($order, 'delivered');

        return redirect()->route('vendor.orders.show', $order)->with('success', 'Order marked as delivered.');
    }

    private function transitionVendorOrder(Order $order, string $status, ?string $reason = null): void
    {
        $this->authorizeVendorOrder($order);

        match ($status) {
            'accepted' => app(VendorOrderWorkflowService::class)->accept($order),
            'cancelled' => app(VendorOrderWorkflowService::class)->reject($order, $reason),
            'processing' => app(VendorOrderWorkflowService::class)->markProcessing($order),
            'dispatched' => app(VendorOrderWorkflowService::class)->markDispatched($order),
            'delivered' => app(VendorOrderWorkflowService::class)->markDelivered($order),
            default => abort(422, 'Unsupported vendor order action.'),
        };

        $this->dispatchCustomerStatusNotification($order->fresh(['customer', 'vendor']), $status);
    }

    private function authorizeVendorOrder(Order $order): void
    {
        abort_if((int) $order->vendor_id !== (int) Auth::guard('vendor')->id(), 404);
    }

    private function dispatchCustomerStatusNotification(Order $order, string $status): void
    {
        try {
            $customer = $order->customer;

            if (! $customer) {
                return;
            }

            event(new TriggerNotificationEvent('ORDER_STATUS_UPDATE', [
                'recipient_type' => 'customer',
                'recipient_id' => $customer->id,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'name' => $customer->name,
                'vendor_name' => $order->vendor?->vendor_name,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => ucfirst($status),
                'order_status' => ucfirst($status),
                'amount' => number_format((float) $order->total_amount, 2),
                'total_amount' => number_format((float) $order->total_amount, 2),
            ]));
        } catch (Throwable $exception) {
            Log::error('Vendor order status notification failed.', [
                'order_id' => $order->id,
                'status' => $status,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
