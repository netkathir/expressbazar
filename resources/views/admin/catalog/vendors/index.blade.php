@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Vendors</h2>
            <p>Manage vendor stores and their mapped delivery locations.</p>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-primary" href="{{ route('admin.vendors.create') }}">Add Vendor</a>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Locations</th>
                        <th>Products</th>
                        <th>Rating</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vendors as $vendor)
                        <tr>
                            <td><strong>{{ $vendor->name }}</strong></td>
                            <td>{{ $vendor->address ?? 'N/A' }}</td>
                            <td>
                                {{ $vendor->locations->map(fn ($location) => $location->city . ' - ' . $location->pincode)->implode(', ') ?: 'None' }}
                            </td>
                            <td>{{ $vendor->products_count }}</td>
                            <td>{{ number_format((float) $vendor->rating, 1) }}</td>
                            <td><span class="admin-badge {{ $vendor->is_active ? 'success' : 'warning' }}">{{ $vendor->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="btn btn-outline" href="{{ route('admin.vendors.edit', $vendor) }}">Edit</a>
                                    <form action="{{ route('admin.vendors.destroy', $vendor) }}" method="POST" onsubmit="return confirm('Delete this vendor?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-muted">No vendors found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $vendors->links('admin.partials.pagination') }}
    </section>
@endsection
