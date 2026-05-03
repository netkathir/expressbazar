<?php

namespace App\Http\Controllers\Admin;

use App\Events\OrderPlaced;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Services\OrderLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['customer', 'vendor'])
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
        ]);
    }

    public function create()
    {
        return view('admin.orders.form', [
            'title' => 'Add Order',
            'activeMenu' => 'orders',
            'order' => new Order(),
            'customers' => User::where('role', 'customer')->orderBy('name')->get(),
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateOrder($request);
        $data['order_number'] = $data['order_number'] ?: 'ORD-'.Str::upper(Str::random(8));
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;
        $data['placed_at'] = $data['placed_at'] ?? now();

        $order = Order::create($data);
        app(OrderLifecycleService::class)->log($order, null, $order->order_status, $request->user()?->id, 'Manual order created from admin.');

        try {
            event(new OrderPlaced($order->loadMissing(['customer', 'vendor', 'items'])));
        } catch (Throwable $exception) {
            Log::error('Admin order placed event dispatch failed.', [
                'order_id' => $order->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('admin.orders.index')->with('success', 'Order created successfully.');
    }

    public function show(Order $order)
    {
        return view('admin.orders.show', [
            'title' => 'Order Details',
            'activeMenu' => 'orders',
            'order' => $order->load(['customer', 'vendor', 'items']),
        ]);
    }

    public function edit(Order $order)
    {
        return view('admin.orders.form', [
            'title' => 'Edit Order',
            'activeMenu' => 'orders',
            'order' => $order,
            'customers' => User::where('role', 'customer')->orderBy('name')->get(),
            'vendors' => Vendor::orderBy('vendor_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Order $order)
    {
        DB::transaction(function () use ($request, $order) {
            $data = $this->validateOrder($request, $order);
            $newStatus = $data['order_status'];
            unset($data['order_status']);

            $data['order_number'] = $data['order_number'] ?: $order->order_number;
            $data['updated_by'] = $request->user()?->id;

            $currentStatus = $order->order_status;
            $order->update($data);

            if ($newStatus !== $currentStatus) {
                app(OrderLifecycleService::class)->transition($order->fresh(), $newStatus, $request->user()?->id, 'Admin updated order status.');
            }
        });

        return redirect()->route('admin.orders.index')->with('success', 'Order updated successfully.');
    }

    public function destroy(Order $order)
    {
        $this->deleteFromDatabase($order);

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted successfully.');
    }

    private function validateOrder(Request $request, ?Order $order = null): array
    {
        return $request->validate([
            'order_number' => ['nullable', 'string', 'max:255', Rule::unique('orders', 'order_number')->ignore($order?->id)],
            'customer_id' => ['nullable', 'exists:users,id'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'delivery_charge' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', Rule::in(['pending', 'paid', 'failed', 'refunded'])],
            'order_status' => ['required', Rule::in(['pending', 'accepted', 'processing', 'dispatched', 'delivered', 'completed', 'cancelled'])],
            'placed_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
