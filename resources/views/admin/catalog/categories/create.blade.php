@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Create Category</h2>
            <p>Add a new merchandising group for your storefront.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.categories.store') }}">
            @csrf
            <div class="admin-grid cols-2">
                <label class="field">
                    <span>Name</span>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                </label>
                <label class="field">
                    <span>Slug</span>
                    <input type="text" name="slug" value="{{ old('slug') }}" class="form-control" placeholder="auto-generated if blank">
                </label>
                <label class="field">
                    <span>Color</span>
                    <input type="text" name="color" value="{{ old('color', '#2563eb') }}" class="form-control" placeholder="#2563eb">
                </label>
                <div></div>
                <label class="field full">
                    <span>Description</span>
                    <textarea name="description" rows="4" class="form-control">{{ old('description') }}</textarea>
                </label>
            </div>
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Save Category</button>
                <a class="btn btn-ghost" href="{{ route('admin.categories') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
