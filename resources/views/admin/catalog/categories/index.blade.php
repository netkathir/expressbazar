@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Categories</h2>
            <p>Organize products into clean ecommerce sections.</p>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-primary" href="{{ route('admin.categories.create') }}">Add Category</a>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Subcategories</th>
                        <th>Color</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td><strong>{{ $category->name }}</strong></td>
                            <td>{{ $category->slug }}</td>
                            <td>{{ $category->subcategories_count }}</td>
                            <td>
                                <span class="admin-badge" style="background: {{ $category->color }}22; color: {{ $category->color }};">
                                    {{ $category->color }}
                                </span>
                            </td>
                            <td>
                                <div class="admin-row-actions">
                                    <a class="btn btn-outline" href="{{ route('admin.categories.edit', $category) }}">Edit</a>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline" type="submit">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="admin-muted">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $categories->links('admin.partials.pagination') }}
    </section>
@endsection
