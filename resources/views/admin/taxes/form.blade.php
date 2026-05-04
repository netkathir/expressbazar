@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Tax' : 'Edit Tax' }}</h1>
                </div>
                <a href="{{ route('admin.taxes.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.taxes.store') : route('admin.taxes.update', $tax) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Tax Name</label>
                    <input type="text" name="tax_name" value="{{ old('tax_name', $tax->tax_name) }}" class="form-control" required pattern="^(?=.*[A-Za-z0-9])[A-Za-z0-9 .&'()\/-]+$">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Percentage</label>
                    <input type="number" name="tax_percentage" step="0.01" min="0" max="100" value="{{ old('tax_percentage', $tax->tax_percentage) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $tax->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $tax->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select">
                        <option value="">Optional</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $tax->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Region</label>
                    <input type="text" name="region_name" value="{{ old('region_name', $tax->region_name) }}" class="form-control" placeholder="Optional region or zone name" pattern="^(?=.*[A-Za-z0-9])[A-Za-z0-9 .&'()\/-]+$">
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Tax' : 'Update Tax' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
