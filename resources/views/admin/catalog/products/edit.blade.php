@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Edit Product</h2>
            <p>Update pricing, inventory, and merchandising details.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.products.update', $product) }}">
            @csrf
            @method('PUT')
            @include('admin.catalog.products._form', ['product' => $product])
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Update Product</button>
                <a class="btn btn-ghost" href="{{ route('admin.products') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
