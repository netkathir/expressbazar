@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Role' : 'Edit Role' }}</h1>
                </div>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.roles.store') : route('admin.roles.update', $role) }}">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif
                @php
                    $isAdminRole = strtolower((string) old('role_name', $role->role_name)) === 'admin';
                @endphp

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
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h2 class="h5 mb-0">Permissions</h2>
                            <label class="form-check-label">
                                <input type="checkbox" id="selectAllPermissions" class="form-check-input me-1" @disabled(empty($modules))>
                                Select All
                            </label>
                        </div>
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
                                            <td class="fw-semibold">
                                                <input type="checkbox" class="form-check-input me-2 module-select" data-module="{{ $moduleKey }}" @checked($isAdminRole)>
                                                {{ $moduleLabel }}
                                            </td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][view]" value="1" class="permission-checkbox" data-module="{{ $moduleKey }}" @checked($isAdminRole || old("permissions.$moduleKey.view", $existing?->can_view))></td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][create]" value="1" class="permission-checkbox" data-module="{{ $moduleKey }}" @checked($isAdminRole || old("permissions.$moduleKey.create", $existing?->can_create))></td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][edit]" value="1" class="permission-checkbox" data-module="{{ $moduleKey }}" @checked($isAdminRole || old("permissions.$moduleKey.edit", $existing?->can_edit))></td>
                                            <td><input type="checkbox" name="permissions[{{ $moduleKey }}][delete]" value="1" class="permission-checkbox" data-module="{{ $moduleKey }}" @checked($isAdminRole || old("permissions.$moduleKey.delete", $existing?->can_delete))></td>
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

@push('scripts')
    <script>
        (function () {
            const selectAll = document.getElementById('selectAllPermissions');
            const checkboxes = Array.from(document.querySelectorAll('.permission-checkbox'));
            const moduleCheckboxes = Array.from(document.querySelectorAll('.module-select'));

            if (!selectAll || checkboxes.length === 0) {
                return;
            }

            function updateModuleState(moduleCheckbox) {
                const module = moduleCheckbox.dataset.module;
                const modulePermissions = checkboxes.filter(function (checkbox) {
                    return checkbox.dataset.module === module;
                });
                const allChecked = modulePermissions.every(function (checkbox) {
                    return checkbox.checked;
                });
                const noneChecked = modulePermissions.every(function (checkbox) {
                    return !checkbox.checked;
                });

                moduleCheckbox.checked = allChecked;
                moduleCheckbox.indeterminate = !allChecked && !noneChecked;
            }

            function updateSelectAllState() {
                const allChecked = checkboxes.every(function (checkbox) {
                    return checkbox.checked;
                });
                const noneChecked = checkboxes.every(function (checkbox) {
                    return !checkbox.checked;
                });

                selectAll.checked = allChecked;
                selectAll.indeterminate = !allChecked && !noneChecked;
            }

            function refreshStates() {
                moduleCheckboxes.forEach(updateModuleState);
                updateSelectAllState();
            }

            selectAll.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });
                refreshStates();
            });

            moduleCheckboxes.forEach(function (moduleCheckbox) {
                moduleCheckbox.addEventListener('change', function () {
                    checkboxes
                        .filter(function (checkbox) {
                            return checkbox.dataset.module === moduleCheckbox.dataset.module;
                        })
                        .forEach(function (checkbox) {
                            checkbox.checked = moduleCheckbox.checked;
                        });

                    refreshStates();
                });
            });

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', refreshStates);
            });

            refreshStates();
        })();
    </script>
@endpush
