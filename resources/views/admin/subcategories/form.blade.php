@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Subcategory' : 'Edit Subcategory' }}</h1>
                    <p class="text-secondary mb-0">Subcategory master under a category.</p>
                </div>
                <a href="{{ route('admin.subcategories.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.subcategories.store') : route('admin.subcategories.update', $subcategory) }}" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) old('category_id', $subcategory->category_id) === (string) $category->id)>{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Subcategory Name</label>
                    <input type="text" name="subcategory_name" value="{{ old('subcategory_name', $subcategory->subcategory_name) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $subcategory->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $subcategory->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Subcategory' : 'Update Subcategory' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
