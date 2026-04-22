@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h1 class="h3 mb-1">{{ $customer->name }}</h1>
                    <p class="text-secondary mb-0">{{ $customer->email }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Profile Info</h2>
                    <ul class="list-unstyled mb-0 info-list">
                        <li><strong>Name:</strong> {{ $customer->name }}</li>
                        <li><strong>Email:</strong> {{ $customer->email }}</li>
                        <li><strong>Phone:</strong> {{ $customer->phone ?: '-' }}</li>
                        <li><strong>Status:</strong> {{ ucfirst($customer->status) }}</li>
                        <li><strong>Created:</strong> {{ optional($customer->created_at)->format('d M Y, h:i A') }}</li>
                        <li><strong>Last Login:</strong> {{ $customer->last_login_at ? $customer->last_login_at->format('d M Y, h:i A') : '-' }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Account Notes</h2>
                    <p class="text-secondary mb-3">Password is not editable here, which keeps sensitive data protected.</p>
                    <div class="soft-card p-3 mb-3">
                        Address and order history modules can be linked here once the customer address and order tables are added.
                    </div>
                    <form action="{{ route('admin.customers.toggle-status', $customer) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning">Toggle Status</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
