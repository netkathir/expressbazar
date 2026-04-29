@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Payment' : 'Edit Payment' }}</h1>
                </div>
                <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.payments.store') : route('admin.payments.update', $payment) }}" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-4">
                    <label class="form-label">Order</label>
                    <select name="order_id" class="form-select">
                        <option value="">Optional</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" @selected((string) old('order_id', $payment->order_id) === (string) $order->id)>{{ $order->order_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Transaction ID</label>
                    <input type="text" name="transaction_id" value="{{ old('transaction_id', $payment->transaction_id) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method" class="form-select" required>
                        @foreach (['stripe', 'cod', 'bank_transfer', 'wallet'] as $method)
                            <option value="{{ $method }}" @selected(old('payment_method', $payment->payment_method ?: 'stripe') === $method)>{{ strtoupper($method) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0" name="amount" value="{{ old('amount', $payment->amount) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        @foreach (['pending', 'paid', 'failed', 'refunded'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $payment->status ?: 'pending') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Paid At</label>
                    <input type="datetime-local" name="paid_at" value="{{ old('paid_at', $payment->paid_at ? $payment->paid_at->format('Y-m-d\TH:i') : '') }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Gateway Response</label>
                    <textarea name="gateway_response" rows="5" class="form-control">{{ old('gateway_response', $payment->gateway_response) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Payment' : 'Update Payment' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
