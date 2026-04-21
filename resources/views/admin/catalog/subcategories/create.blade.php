@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Create Subcategory</h2>
            <p>Add a smaller shopping group under a category.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.subcategories.store') }}">
            @csrf
            @include('admin.catalog.subcategories._form')
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Save Subcategory</button>
                <a class="btn btn-ghost" href="{{ route('admin.subcategories') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
