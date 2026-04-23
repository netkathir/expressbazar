@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">User & Role Management</h1>
                <p class="text-secondary mb-0">Create admin roles and assign module permissions.</p>
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
                        <th>Permissions</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td class="fw-semibold">{{ $role->role_name }}</td>
                            <td>{{ $role->description ?: '-' }}</td>
                            <td>
                                <div class="small text-secondary">
                                    @forelse ($role->permissions as $permission)
                                        <div>{{ $permission->module_name }}: V{{ (int) $permission->can_view }} C{{ (int) $permission->can_create }} E{{ (int) $permission->can_edit }} D{{ (int) $permission->can_delete }}</div>
                                    @empty
                                        -
                                    @endforelse
                                </div>
                            </td>
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
