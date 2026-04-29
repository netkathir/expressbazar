@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Payment Management</h1>
            </div>
            <a href="{{ route('admin.payments.create') }}" class="btn btn-primary">Add Payment</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Transaction ID">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All</option>
                        @foreach (['stripe', 'cod', 'bank_transfer', 'wallet'] as $method)
                            <option value="{{ $method }}" @selected(request('payment_method') === $method)>{{ strtoupper($method) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach (['pending', 'paid', 'failed', 'refunded'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.payments.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Transaction</th>
                        <th>Order</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Paid At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="fw-semibold">{{ $payment->transaction_id }}</td>
                            <td>{{ $payment->order?->order_number ?? '-' }}</td>
                            <td>{{ strtoupper($payment->payment_method) }}</td>
                            <td>{{ number_format((float) $payment->amount, 2) }}</td>
                            <td><span class="badge text-bg-{{ $payment->status === 'paid' ? 'success' : 'secondary' }}">{{ ucfirst($payment->status) }}</span></td>
                            <td>{{ $payment->paid_at?->format('M d, Y h:i A') ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit payment" title="Edit payment">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.payments.destroy', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this payment?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete payment" title="Delete payment">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $payments->links() }}
        </div>
    </div>
@endsection
