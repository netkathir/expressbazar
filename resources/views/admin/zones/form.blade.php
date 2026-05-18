@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Region / Zone' : 'Edit Region / Zone' }}</h1>
                </div>
                <a href="{{ route('admin.zones.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.zones.store') : route('admin.zones.update', $zone) }}" class="row g-3" data-dirty-check data-zone-form>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select" required data-country-select>
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $zone->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">State</label>
                    <select class="form-select" data-state-select data-selected-state="{{ old('state', $zone->city?->state) }}">
                        <option value="">Select state</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">City</label>
                    <select name="city_id" class="form-select" required data-city-select data-selected-city="{{ old('city_id', $zone->city_id) }}">
                        <option value="">Select city</option>
                        @foreach ($cities as $city)
                            <option value="{{ $city->id }}" @selected((string) old('city_id', $zone->city_id) === (string) $city->id)>{{ $city->city_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Zone Name</label>
                    <input type="text" name="zone_name" value="{{ old('zone_name', $zone->zone_name) }}" class="form-control" required pattern="^(?=.*[A-Za-z0-9])[A-Za-z0-9 .'()\/-]+$">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Zone Code</label>
                    <input type="text" name="zone_code" value="{{ old('zone_code', $zone->zone_code) }}" class="form-control text-uppercase" maxlength="20" pattern="^[A-Za-z0-9-]+$">
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

@push('scripts')
    <script>
        (function () {
            const form = document.querySelector('[data-zone-form]');
            if (!form) {
                return;
            }

            const countrySelect = form.querySelector('[data-country-select]');
            const stateSelect = form.querySelector('[data-state-select]');
            const citySelect = form.querySelector('[data-city-select]');
            const selectedState = stateSelect?.dataset.selectedState || '';
            const selectedCity = citySelect?.dataset.selectedCity || '';
            const states = @json($stateOptions);
            const cities = @json($cityOptions);

            if (!countrySelect || !stateSelect || !citySelect) {
                return;
            }

            function appendPlaceholder(text) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = text;
                citySelect.appendChild(option);
            }

            function appendStatePlaceholder(text) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = text;
                stateSelect.appendChild(option);
            }

            function populateStates(countryId, state = '') {
                stateSelect.innerHTML = '';

                if (!countryId) {
                    appendStatePlaceholder('Select country first');
                    stateSelect.value = '';
                    stateSelect.disabled = true;
                    return '';
                }

                const filteredStates = states.filter(function (item) {
                    return item.country_id === countryId;
                });

                appendStatePlaceholder(filteredStates.length ? 'Select state' : 'No states available');

                filteredStates.forEach(function (item) {
                    const option = document.createElement('option');
                    option.value = item.state;
                    option.textContent = item.state;
                    stateSelect.appendChild(option);
                });

                stateSelect.disabled = filteredStates.length === 0;
                stateSelect.value = filteredStates.some(function (item) {
                    return item.state === state;
                }) ? state : '';

                return stateSelect.value;
            }

            function populateCities(countryId, state = '', cityId = '') {
                citySelect.innerHTML = '';

                if (!countryId) {
                    appendPlaceholder('Select country first');
                    citySelect.value = '';
                    citySelect.disabled = true;
                    return;
                }

                const filteredCities = cities.filter(function (city) {
                    return city.country_id === countryId && (!state || city.state === state);
                });

                appendPlaceholder(filteredCities.length ? 'Select city' : 'No cities available');

                filteredCities.forEach(function (city) {
                    const option = document.createElement('option');
                    option.value = city.id;
                    option.textContent = city.city_name;
                    citySelect.appendChild(option);
                });

                citySelect.disabled = false;
                citySelect.value = filteredCities.some(function (city) {
                    return city.id === cityId;
                }) ? cityId : '';
            }

            const resolvedState = populateStates(countrySelect.value, selectedState);
            populateCities(countrySelect.value, resolvedState, selectedCity);

            countrySelect.addEventListener('change', function () {
                populateStates(countrySelect.value);
                populateCities(countrySelect.value);
            });

            stateSelect.addEventListener('change', function () {
                populateCities(countrySelect.value, stateSelect.value);
            });
        })();
    </script>
@endpush
