@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Customer Management</h1>
            </div>
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">Add Customer</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, email or phone">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="fw-semibold">{{ $customer->name }}</td>
                            <td>{{ $customer->email }}</td>
                            <td>{{ $customer->phone ?: '-' }}</td>
                            <td><span class="badge text-bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($customer->status) }}</span></td>
                            <td>{{ optional($customer->created_at)->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit customer" title="Edit customer">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.customers.destroy', $customer) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this customer?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete customer" title="Delete customer">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $customers->links() }}
        </div>
    </div>
@endsection
