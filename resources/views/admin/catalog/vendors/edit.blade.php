@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Edit Vendor</h2>
            <p>Update the vendor store profile.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.vendors.update', $vendor) }}">
            @csrf
            @method('PUT')
            @include('admin.catalog.vendors._form', ['vendor' => $vendor])
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Update Vendor</button>
                <a class="btn btn-ghost" href="{{ route('admin.vendors') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
