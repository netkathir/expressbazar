@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add City' : 'Edit City' }}</h1>
                </div>
                <a href="{{ route('admin.cities.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.cities.store') : route('admin.cities.update', $city) }}" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select" required>
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $city->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">State / Province</label>
                    <input type="text" name="state" value="{{ old('state', $city->state) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">City Name</label>
                    <input type="text" name="city_name" value="{{ old('city_name', $city->city_name) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">City Code</label>
                    <input type="text" name="city_code" value="{{ old('city_code', $city->city_code) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $city->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $city->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save City' : 'Update City' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
