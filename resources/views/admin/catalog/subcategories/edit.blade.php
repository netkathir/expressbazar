@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Edit Subcategory</h2>
            <p>Update the subcategory details.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.subcategories.update', $subcategory) }}">
            @csrf
            @method('PUT')
            @include('admin.catalog.subcategories._form', ['subcategory' => $subcategory])
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Update Subcategory</button>
                <a class="btn btn-ghost" href="{{ route('admin.subcategories') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
