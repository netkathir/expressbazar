@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Country Management</h1>
            </div>
            <a href="{{ route('admin.countries.create') }}" class="btn btn-primary">Add Country</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Country name, code or currency">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.countries.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Country Name</th>
                        <th>Code</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Cities</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($countries as $country)
                        <tr>
                            <td class="fw-semibold">{{ $country->country_name }}</td>
                            <td>{{ $country->country_code }}</td>
                            <td>{{ $country->currency }}</td>
                            <td><span class="badge text-bg-{{ $country->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($country->status) }}</span></td>
                            <td>{{ $country->cities_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.countries.edit', $country) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit country" title="Edit country">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.countries.destroy', $country) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this country?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete country" title="Delete country">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No countries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $countries->links() }}
        </div>
    </div>
@endsection
