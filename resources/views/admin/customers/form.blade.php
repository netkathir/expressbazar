@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Customer' : 'Edit Customer' }}</h1>
                    <p class="text-secondary mb-0">Customer accounts with password hidden from admin editing.</p>
                </div>
                <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.customers.store') : route('admin.customers.update', $customer) }}" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $customer->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $customer->status) === 'inactive')>Inactive</option>
                    </select>
                </div>

                @if ($mode === 'create')
                    <div class="col-12">
                        <div class="alert alert-info mb-0">A secure password will be generated automatically for the new customer account.</div>
                    </div>
                @endif

                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Customer' : 'Update Customer' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
