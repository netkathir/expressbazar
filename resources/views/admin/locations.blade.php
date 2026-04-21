@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Locations</h2>
            <p>Configured city and pincode mappings for vendor delivery coverage.</p>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>City</th>
                        <th>Pincode</th>
                        <th>Vendors</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr>
                            <td><strong>{{ $location->city }}</strong></td>
                            <td>{{ $location->pincode }}</td>
                            <td>{{ $location->vendors_count ?? $location->vendors()->count() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="admin-muted">No locations configured.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $locations->links('admin.partials.pagination') }}
    </section>
@endsection
