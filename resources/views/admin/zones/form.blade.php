@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Region / Zone' : 'Edit Region / Zone' }}</h1>
                    <p class="text-secondary mb-0">Delivery areas under a city.</p>
                </div>
                <a href="{{ route('admin.zones.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.zones.store') : route('admin.zones.update', $zone) }}" class="row g-3">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-4">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select" required>
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $zone->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <select name="city_id" class="form-select" required>
                        <option value="">Select city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) old('city_id', $zone->city_id) === (string) $city->id)>{{ $city->city_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Zone Name</label>
                    <input type="text" name="zone_name" value="{{ old('zone_name', $zone->zone_name) }}" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Zone Code</label>
                    <input type="text" name="zone_code" value="{{ old('zone_code', $zone->zone_code) }}" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Delivery Available</label>
                    <select name="delivery_available" class="form-select" required>
                        <option value="1" @selected((string) old('delivery_available', (int) $zone->delivery_available) === '1')>Yes</option>
                        <option value="0" @selected((string) old('delivery_available', (int) $zone->delivery_available) === '0')>No</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $zone->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $zone->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Zone' : 'Update Zone' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
