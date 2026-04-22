@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Tax Master</h1>
                <p class="text-secondary mb-0">Create and maintain tax rules for products and checkout.</p>
            </div>
            <a href="{{ route('admin.taxes.create') }}" class="btn btn-primary">Add Tax</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Tax name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Country / Region</label>
                    <select name="country_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) request('country_id') === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
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
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.taxes.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Tax Name</th>
                        <th>Percentage</th>
                        <th>Country</th>
                        <th>Region</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($taxes as $tax)
                        <tr>
                            <td class="fw-semibold">{{ $tax->tax_name }}</td>
                            <td>{{ number_format($tax->tax_percentage, 2) }}%</td>
                            <td>{{ $tax->country?->country_name ?: '-' }}</td>
                            <td>{{ $tax->region_name ?: '-' }}</td>
                            <td><span class="badge text-bg-{{ $tax->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($tax->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.taxes.edit', $tax) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                <form action="{{ route('admin.taxes.destroy', $tax) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this tax?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No taxes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $taxes->links() }}
        </div>
    </div>
@endsection
