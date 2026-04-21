@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Create Product</h2>
            <p>Add a new product for your ecommerce catalog.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.products.store') }}">
            @csrf
            @include('admin.catalog.products._form')
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Save Product</button>
                <a class="btn btn-ghost" href="{{ route('admin.products') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
