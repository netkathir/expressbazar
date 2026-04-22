@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Category' : 'Edit Category' }}</h1>
                    <p class="text-secondary mb-0">Top-level category setup.</p>
                </div>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.categories.store') : route('admin.categories.update', $category) }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="category_name" value="{{ old('category_name', $category->category_name) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $category->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $category->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Image</label>
                    <input type="file" name="image" class="form-control">
                </div>

                @if ($mode === 'edit' && $category->image_path)
                    <div class="col-12">
                        <div class="small text-secondary mb-2">Current image</div>
                        <img src="{{ asset($category->image_path) }}" alt="{{ $category->category_name }}" style="width: 96px; height: 96px; object-fit: cover; border-radius: 16px;">
                    </div>
                @endif

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Category' : 'Update Category' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
