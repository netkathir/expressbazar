@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Country' : 'Edit Country' }}</h1>
                </div>
                <a href="{{ route('admin.countries.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.countries.store') : route('admin.countries.update', $country) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Country Name</label>
                    <input type="text" name="country_name" value="{{ old('country_name', $country->country_name) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country Code</label>
                    <input type="text" name="country_code" value="{{ old('country_code', $country->country_code) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" value="{{ old('currency', $country->currency) }}" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" value="{{ old('timezone', $country->timezone) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $country->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $country->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Country' : 'Update Country' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
