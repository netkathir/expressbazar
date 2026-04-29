@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Admin User Management</h1>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add Admin User</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Name, email or phone">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->role_name }}" @selected(request('role') === $role->role_name)>{{ $role->role_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Permission Count</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->username ?: '-' }}</td>
                            <td>{{ $user->role }}</td>
                            <td>{{ (int) $user->permissions_count }}</td>
                            <td><span class="badge text-bg-{{ $user->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($user->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit admin user" title="Edit admin user">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this admin user?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete admin user" title="Delete admin user">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-5">No admin users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $users->links() }}
        </div>
    </div>
@endsection
