@extends('admin.layout')

@section('content')
    <section class="admin-page-head">
        <div>
            <h2>Create Vendor</h2>
            <p>Add a vendor store and assign delivery locations.</p>
        </div>
    </section>

    <section class="admin-card">
        <form method="POST" action="{{ route('admin.vendors.store') }}">
            @csrf
            @include('admin.catalog.vendors._form')
            <div class="admin-form-actions">
                <button class="btn btn-primary" type="submit">Save Vendor</button>
                <a class="btn btn-ghost" href="{{ route('admin.vendors') }}">Cancel</a>
            </div>
        </form>
    </section>
@endsection
