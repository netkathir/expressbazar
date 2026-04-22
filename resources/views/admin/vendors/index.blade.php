@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Vendor Master</h1>
                <p class="text-secondary mb-0">Manage vendors, inventory mode and EPOS settings.</p>
            </div>
            <a href="{{ route('admin.vendors.create') }}" class="btn btn-primary">Add Vendor</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Vendor name, email or phone">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Inventory Mode</label>
                    <select name="inventory_mode" class="form-select">
                        <option value="">All</option>
                        <option value="internal" @selected(request('inventory_mode') === 'internal')>Internal</option>
                        <option value="epos" @selected(request('inventory_mode') === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Inventory</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="sf-avatar sf-avatar-sm">
                                        @if ($vendor->logo_path)
                                            <img src="{{ asset($vendor->logo_path) }}" alt="{{ $vendor->vendor_name }}">
                                        @else
                                            {{ strtoupper(substr($vendor->vendor_name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $vendor->vendor_name }}</div>
                                        <div class="text-secondary small">{{ $vendor->country?->country_code ?: 'UK' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $vendor->email }}</td>
                            <td>{{ $vendor->phone ?: '-' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $vendor->inventory_mode === 'epos' ? 'info' : 'secondary' }}">
                                    {{ strtoupper($vendor->inventory_mode) }}
                                </span>
                            </td>
                            <td>{{ $vendor->country?->country_name }} / {{ $vendor->city?->city_name }} / {{ $vendor->zone?->zone_name }}</td>
                            <td><span class="badge text-bg-{{ $vendor->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($vendor->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.vendors.edit', $vendor) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.vendors.destroy', $vendor) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this vendor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No vendors found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $vendors->links() }}
        </div>
    </div>
@endsection
