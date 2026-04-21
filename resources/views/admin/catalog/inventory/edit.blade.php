@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Edit Inventory Item</h2>
            <p>Adjust vendor pricing and stock levels.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.inventory.update', $inventory) }}">
            @csrf
            @method('PUT')
            @include('admin.catalog.inventory._form', ['inventory' => $inventory])
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Update Inventory Item</button>
                <a class="btn btn-ghost" href="{{ route('admin.inventory') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
