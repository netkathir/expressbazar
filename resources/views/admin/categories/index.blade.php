@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Category Master</h1>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Add Category</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Category name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-semibold">{{ $category->category_name }}</td>
                            <td>
                                @if ($category->image_path)
                                    <img src="{{ asset($category->image_path) }}" alt="{{ $category->category_name }}" style="width: 48px; height: 48px; object-fit: cover; border-radius: 12px;">
                                @else
                                    <span class="text-secondary">-</span>
                                @endif
                            </td>
                            <td><span class="badge text-bg-{{ $category->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($category->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit category" title="Edit category">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form
                                    action="{{ route('admin.categories.destroy', $category) }}"
                                    method="POST"
                                    class="d-inline js-confirm-delete"
                                    data-confirm-message="Delete this {{ $category->status === 'active' ? 'active ' : '' }}category?"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete category" title="Delete category">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">No categories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $categories->links() }}
        </div>
    </div>
@endsection
