@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Create Inventory Item</h2>
            <p>Set a vendor price and available stock for a product.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.inventory.store') }}">
            @csrf
            @include('admin.catalog.inventory._form')
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Save Inventory Item</button>
                <a class="btn btn-ghost" href="{{ route('admin.inventory') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
