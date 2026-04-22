@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Admin User' : 'Edit Admin User' }}</h1>
                    <p class="text-secondary mb-0">Create a backend user and assign a role from the role master.</p>
                </div>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.users.store') : route('admin.users.update', $user) }}" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" class="form-control" placeholder="Optional but recommended">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">Select role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->role_name }}" @selected(old('role', $user->role) === $role->role_name)>{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $user->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $user->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Password {{ $mode === 'edit' ? '(leave blank to keep current)' : '' }}</label>
                    <input type="password" name="password" class="form-control" {{ $mode === 'create' ? 'required' : '' }}>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Admin User' : 'Update Admin User' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
