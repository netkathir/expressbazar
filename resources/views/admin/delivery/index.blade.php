@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Delivery & Logistics</h1>
            </div>
            <a href="{{ route('admin.delivery.create') }}" class="btn btn-primary">Add Delivery Config</a>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>City</th>
                        <th>Zone</th>
                        <th>Delivery</th>
                        <th>Charge</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($configs as $config)
                        <tr>
                            <td>{{ $config->country?->country_name ?? '-' }}</td>
                            <td>{{ $config->city?->city_name ?? '-' }}</td>
                            <td>{{ $config->zone?->zone_name ?? '-' }}</td>
                            <td><span class="badge text-bg-{{ $config->delivery_available ? 'success' : 'secondary' }}">{{ $config->delivery_available ? 'Yes' : 'No' }}</span></td>
                            <td>{{ number_format((float) $config->delivery_charge, 2) }}</td>
                            <td><span class="badge text-bg-{{ $config->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($config->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.delivery.edit', $config) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit delivery configuration" title="Edit delivery configuration">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.delivery.destroy', $config) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this configuration?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete delivery configuration" title="Delete delivery configuration">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No delivery configurations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $configs->links() }}
        </div>
    </div>
@endsection
