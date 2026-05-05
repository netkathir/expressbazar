@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Region / Zone Management</h1>
            </div>
            <a href="{{ route('admin.zones.create') }}" class="btn btn-primary">Add Region / Zone</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Zone name or code">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) request('country_id') === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <select name="city_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) request('city_id') === (string) $city->id)>{{ $city->city_name }}</option>
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
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.zones.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive admin-zones-table-wrap">
            <table class="table table-hover align-middle mb-0 admin-zones-table">
                <colgroup>
                    <col class="admin-zones-name-col">
                    <col class="admin-zones-country-col">
                    <col class="admin-zones-city-col">
                    <col class="admin-zones-code-col">
                    <col class="admin-zones-delivery-col">
                    <col class="admin-zones-status-col">
                    <col class="admin-zones-actions-col">
                </colgroup>
                <thead>
                    <tr>
                        <th class="admin-zones-name-col">Zone Name</th>
                        <th class="admin-zones-country-col">Country</th>
                        <th class="admin-zones-city-col">City</th>
                        <th class="admin-zones-code-col">Zone Code</th>
                        <th class="admin-zones-delivery-col">Delivery</th>
                        <th class="admin-zones-status-col">Status</th>
                        <th class="admin-zones-actions-col text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($zones as $zone)
                        <tr>
                            <td class="fw-semibold admin-zones-text-cell">{{ $zone->zone_name }}</td>
                            <td>{{ $zone->country?->country_name }}</td>
                            <td>{{ $zone->city?->city_name }}</td>
                            <td class="admin-zones-code-cell">{{ $zone->zone_code ?: '-' }}</td>
                            <td>
                                <span class="badge text-bg-{{ $zone->delivery_available ? 'success' : 'warning' }}">
                                    {{ $zone->delivery_available ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td><span class="badge text-bg-{{ $zone->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($zone->status) }}</span></td>
                            <td class="admin-zones-actions-cell text-end">
                                <a href="{{ route('admin.zones.edit', $zone) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit zone" title="Edit zone">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.zones.destroy', $zone) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this zone?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete zone" title="Delete zone">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No zones found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $zones->links() }}
        </div>
    </div>
@endsection
