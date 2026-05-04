@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Order' : 'Edit Order' }}</h1>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.orders.store') : route('admin.orders.update', $order) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-4">
                    <label class="form-label">Order Number</label>
                    <input type="text" name="order_number" value="{{ old('order_number', $order->order_number) }}" class="form-control" placeholder="Optional auto-generated">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-select">
                        <option value="">Optional</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) old('customer_id', $order->customer_id) === (string) $customer->id)>{{ $customer->name }} ({{ $customer->email }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">Optional</option>
                        @foreach ($vendors as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $order->vendor_id) === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Total Amount</label>
                    <input type="number" step="0.01" min="0" name="total_amount" value="{{ old('total_amount', $order->total_amount) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Delivery Charge</label>
                    <input type="number" step="0.01" min="0" name="delivery_charge" value="{{ old('delivery_charge', $order->delivery_charge) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select" required>
                        @foreach (['pending', 'paid', 'failed', 'refunded'] as $status)
                            <option value="{{ $status }}" @selected(old('payment_status', $order->payment_status ?: 'pending') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Order Status</label>
                    <select name="order_status" class="form-select" required>
                        @foreach (['pending', 'accepted', 'processing', 'dispatched', 'delivered', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}" @selected(old('order_status', $order->order_status ?: 'pending') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Placed At</label>
                    <input type="datetime-local" name="placed_at" value="{{ old('placed_at', $order->placed_at ? $order->placed_at->format('Y-m-d\TH:i') : '') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" rows="4" class="form-control">{{ old('notes', $order->notes) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Order' : 'Update Order' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
