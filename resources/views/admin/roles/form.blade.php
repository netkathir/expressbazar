@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Role' : 'Edit Role' }}</h1>
                    <p class="text-secondary mb-0">Define admin permissions for each module.</p>
                </div>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.roles.store') : route('admin.roles.update', $role) }}">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Role Name</label>
                        <input type="text" name="role_name" value="{{ old('role_name', $role->role_name) }}" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" @selected(old('status', $role->status ?: 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $role->status) === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control">{{ old('description', $role->description) }}</textarea>
                    </div>
                </div>

                <div class="card shell-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Permissions</h2>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>View</th>
                                        <th>Create</th>
                                        <th>Edit</th>
                                        <th>Delete</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($modules as $moduleKey => $moduleLabel)
                                        @php
                                            $existing = $role->permissions->firstWhere('module_name', $moduleLabel);
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $moduleLabel }}</td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][view]" value="1" @checked(old("permissions.$moduleKey.view", $existing?->can_view))></td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][create]" value="1" @checked(old("permissions.$moduleKey.create", $existing?->can_create))></td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][edit]" value="1" @checked(old("permissions.$moduleKey.edit", $existing?->can_edit))></td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][delete]" value="1" @checked(old("permissions.$moduleKey.delete", $existing?->can_delete))></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Role' : 'Update Role' }}</button>
            </form>
        </div>
    </div>
@endsection
