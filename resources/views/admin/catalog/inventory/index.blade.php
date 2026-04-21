@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Inventory</h2>
            <p>Manage vendor pricing and stock from one place.</p>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-primary" href="{{ route('admin.inventory.create') }}">Add Inventory Item</a>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        @php
                            $categoryName = $item->product?->subcategory?->category?->name ?? 'Uncategorized';
                            $subcategoryName = $item->product?->subcategory?->name ?? 'Uncategorized';
                        @endphp
                        <tr>
                            <td><strong>{{ $item->vendor?->name }}</strong></td>
                            <td>
                                <div><strong>{{ $item->product?->name }}</strong></div>
                                <div class="admin-muted">{{ $subcategoryName }}</div>
                            </td>
                            <td>{{ $categoryName }}</td>
                            <td>Rs. {{ number_format((float) $item->price) }}</td>
                            <td>{{ number_format((int) $item->stock) }}</td>
                            <td><span class="admin-badge {{ $item->is_active ? 'success' : 'warning' }}">{{ $item->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="btn btn-outline" href="{{ route('admin.inventory.edit', $item) }}">Edit</a>
                                    <form action="{{ route('admin.inventory.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this inventory item?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="admin-muted">No inventory items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $items->links('admin.partials.pagination') }}
    </section>
@endsection
