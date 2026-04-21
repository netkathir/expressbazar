@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Edit Category</h2>
            <p>Update the category details.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.categories.update', $category) }}">
            @csrf
            @method('PUT')
            <div class="admin-grid cols-2">
                <label class="field">
                    <span>Name</span>
                    <input type="text" name="name" value="{{ old('name', $category->name) }}" class="form-control" required>
                </label>
                <label class="field">
                    <span>Slug</span>
                    <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="form-control" required>
                </label>
                <label class="field">
                    <span>Color</span>
                    <input type="text" name="color" value="{{ old('color', $category->color) }}" class="form-control">
                </label>
                <div></div>
                <label class="field full">
                    <span>Description</span>
                    <textarea name="description" rows="4" class="form-control">{{ old('description', $category->description) }}</textarea>
                </label>
            </div>
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Update Category</button>
                <a class="btn btn-ghost" href="{{ route('admin.categories') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
