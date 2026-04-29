@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Subcategory Master</h1>
            </div>
            <a href="{{ route('admin.subcategories.create') }}" class="btn btn-primary">Add Subcategory</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Subcategory name">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->category_name }}</option>
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
                    <a href="{{ route('admin.subcategories.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Subcategory</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($subcategories as $subcategory)
                        <tr>
                            <td class="fw-semibold">{{ $subcategory->subcategory_name }}</td>
                            <td>{{ $subcategory->category?->category_name }}</td>
                            <td><span class="badge text-bg-{{ $subcategory->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($subcategory->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.subcategories.edit', $subcategory) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit subcategory" title="Edit subcategory">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.subcategories.destroy', $subcategory) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this subcategory?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete subcategory" title="Delete subcategory">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-5">No subcategories found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $subcategories->links() }}
        </div>
    </div>
@endsection
