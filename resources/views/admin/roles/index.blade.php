@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">User & Role Management</h1>
            </div>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">Add Role</a>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Permission Count</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td class="fw-semibold">{{ $role->role_name }}</td>
                            <td>{{ $role->description ?: '-' }}</td>
                            <td>{{ (int) $role->permissions_count }}</td>
                            <td><span class="badge text-bg-{{ $role->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($role->status) }}</span></td>
                            <td class="text-end">
                                <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit role" title="Edit role">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this role?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete role" title="Delete role">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-5">No roles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $roles->links() }}
        </div>
    </div>
@endsection
