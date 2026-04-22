@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Inventory Management</h1>
                <p class="text-secondary mb-0">Monitor and adjust internal stock, or inspect EPOS-managed stock.</p>
            </div>
            <a href="{{ route('admin.inventory.create') }}" class="btn btn-primary">Adjust Stock</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Product name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Mode</label>
                    <select name="inventory_mode" class="form-select">
                        <option value="">All</option>
                        <option value="internal" @selected(request('inventory_mode') === 'internal')>Internal</option>
                        <option value="epos" @selected(request('inventory_mode') === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="lowStock" name="low_stock" value="1" @checked(request('low_stock'))>
                        <label class="form-check-label" for="lowStock">Low stock only</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Vendor</th>
                        <th>Mode</th>
                        <th>Stock</th>
                        <th>Unit</th>
                        <th>Threshold</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventories as $inventory)
                        <tr>
                            <td class="fw-semibold">{{ $inventory->product?->product_name ?? '-' }}</td>
                            <td>{{ $inventory->product?->vendor?->vendor_name ?? '-' }}</td>
                            <td><span class="badge text-bg-{{ $inventory->inventory_mode === 'epos' ? 'info' : 'primary' }}">{{ strtoupper($inventory->inventory_mode) }}</span></td>
                            <td>{{ $inventory->stock_quantity }}</td>
                            <td>{{ $inventory->unit ?? '-' }}</td>
                            <td>{{ $inventory->low_stock_threshold ?? '-' }}</td>
                            <td>{{ $inventory->updated_at?->format('M d, Y h:i A') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.inventory.edit', $inventory) }}" class="btn btn-sm btn-outline-primary">Adjust</a>
                                <form action="{{ route('admin.inventory.destroy', $inventory) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this inventory record?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-5">No inventory records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $inventories->links() }}
        </div>
    </div>
@endsection
