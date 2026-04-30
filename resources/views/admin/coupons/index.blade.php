@extends('layouts.admin')

@section('content')
    @php
        $routePrefix = $routePrefix ?? 'admin.coupons';
        $isVendorPanel = $isVendorPanel ?? false;
        $panelUser = $isVendorPanel ? auth('vendor')->user() : auth()->user();
        $canCreateCoupons = $isVendorPanel
            ? $panelUser?->hasRolePermission('coupons', 'create')
            : ($panelUser?->hasRolePermission('coupons', 'create') ?? true);
        $canEditCoupons = $isVendorPanel
            ? $panelUser?->hasRolePermission('coupons', 'edit')
            : ($panelUser?->hasRolePermission('coupons', 'edit') ?? true);
        $canDeleteCoupons = $isVendorPanel
            ? $panelUser?->hasRolePermission('coupons', 'delete')
            : ($panelUser?->hasRolePermission('coupons', 'delete') ?? true);
    @endphp
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Coupon Management</h1>
            </div>
            @if ($canCreateCoupons)
                <a href="{{ route($routePrefix.'.create') }}" class="btn btn-primary">Add Coupon</a>
            @endif
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Coupon code">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All</option>
                        <option value="fixed" @selected(request('type') === 'fixed')>Fixed</option>
                        <option value="percentage" @selected(request('type') === 'percentage')>Percentage</option>
                    </select>
                </div>
                @unless ($isVendorPanel)
                    <div class="col-md-3">
                        <label class="form-label">Vendor</label>
                        <select name="vendor_id" class="form-select">
                            <option value="">All vendors</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route($routePrefix.'.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Discount</th>
                        <th>Min Order</th>
                        <th>Vendor</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td class="fw-semibold">{{ $coupon->code }}</td>
                            <td>
                                {{ $coupon->type === 'percentage' ? rtrim(rtrim(number_format((float) $coupon->value, 2), '0'), '.').'%' : '₹'.number_format((float) $coupon->value, 0) }}
                            </td>
                            <td>{{ $coupon->min_order !== null ? '₹'.number_format((float) $coupon->min_order, 0) : '-' }}</td>
                            <td>{{ $coupon->vendor?->vendor_name ?: 'All vendors' }}</td>
                            <td>{{ $coupon->expires_at ? $coupon->expires_at->format('d M Y') : '-' }}</td>
                            <td><span class="badge text-bg-{{ $coupon->is_active ? 'success' : 'secondary' }}">{{ $coupon->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-end">
                                @if ($canEditCoupons)
                                    <a href="{{ route($routePrefix.'.edit', $coupon) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit coupon" title="Edit coupon">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                @endif
                                @if ($canDeleteCoupons)
                                    <form action="{{ route($routePrefix.'.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this coupon?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete coupon" title="Delete coupon">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No coupons found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $coupons->links() }}
        </div>
    </div>
@endsection
