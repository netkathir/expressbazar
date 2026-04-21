@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Subcategories</h2>
            <p>Split the catalog into smaller shopping groups.</p>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-primary" href="{{ route('admin.subcategories.create') }}">Add Subcategory</a>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Slug</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subcategories as $subcategory)
                        <tr>
                            <td><strong>{{ $subcategory->name }}</strong></td>
                            <td>{{ $subcategory->category?->name ?? 'Unassigned' }}</td>
                            <td>{{ $subcategory->slug }}</td>
                            <td>{{ $subcategory->products_count }}</td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="btn btn-outline" href="{{ route('admin.subcategories.edit', $subcategory) }}">Edit</a>
                                    <form action="{{ route('admin.subcategories.destroy', $subcategory) }}" method="POST" onsubmit="return confirm('Delete this subcategory?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-muted">No subcategories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $subcategories->links('admin.partials.pagination') }}
    </section>
@endsection
