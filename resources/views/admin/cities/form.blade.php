@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add City' : 'Edit City' }}</h1>
                </div>
                <a href="{{ route('admin.cities.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.cities.store') : route('admin.cities.update', $city) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select" required data-country-select>
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $city->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">State / Province</label>
                    <select class="form-select" data-state-select data-selected-state="{{ old('state', $city->state) }}">
                        <option value="">Select state</option>
                    </select>
                    <input type="text" value="{{ old('state', $city->state) }}" class="form-control mt-2 d-none" pattern="^(?=.*[A-Za-z])[A-Za-z .'()-]+$" placeholder="Enter state / province" data-state-custom>
                    <input type="hidden" name="state" value="{{ old('state', $city->state) }}" data-state-value>
                </div>
                <div class="col-md-6">
                    <label class="form-label">City Name</label>
                    <select class="form-select" required data-city-select data-selected-city="{{ old('city_name', $city->city_name) }}">
                        <option value="">Select city</option>
                    </select>
                    <input type="text" value="{{ old('city_name', $city->city_name) }}" class="form-control mt-2 d-none" required pattern="^(?=.*[A-Za-z])[A-Za-z .'()-]+$" placeholder="Enter city name" data-city-custom>
                    <input type="hidden" name="city_name" value="{{ old('city_name', $city->city_name) }}" data-city-value>
                </div>
                <div class="col-md-3">
                    <label class="form-label">City Code</label>
                    <input type="text" name="city_code" value="{{ old('city_code', $city->city_code) }}" class="form-control text-uppercase" maxlength="10" pattern="^[A-Za-z0-9-]+$">
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

@push('scripts')
    <script>
        (function () {
            const countrySelect = document.querySelector('[data-country-select]');
            const stateSelect = document.querySelector('[data-state-select]');
            const stateCustom = document.querySelector('[data-state-custom]');
            const stateValue = document.querySelector('[data-state-value]');
            const citySelect = document.querySelector('[data-city-select]');
            const cityCustom = document.querySelector('[data-city-custom]');
            const cityValue = document.querySelector('[data-city-value]');
            const states = @json($stateOptions);
            const cities = @json($cityOptions);
            const customValue = '__custom__';

            if (!countrySelect || !stateSelect || !stateCustom || !stateValue || !citySelect || !cityCustom || !cityValue) {
                return;
            }

            function addOption(select, value, label) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                select.appendChild(option);
            }

            function optionExists(select, value) {
                return Array.from(select.options).some((option) => option.value === value);
            }

            function setCustomInput(input, visible, required) {
                input.classList.toggle('d-none', !visible);
                input.required = required;
            }

            function syncStateValue() {
                if (stateSelect.value === customValue) {
                    stateValue.value = stateCustom.value;
                    setCustomInput(stateCustom, true, false);
                    return;
                }

                stateValue.value = stateSelect.value;
                stateCustom.value = stateSelect.value;
                setCustomInput(stateCustom, false, false);
            }

            function syncCityValue() {
                if (citySelect.value === customValue) {
                    cityValue.value = cityCustom.value;
                    setCustomInput(cityCustom, true, true);
                    return;
                }

                cityValue.value = citySelect.value;
                cityCustom.value = citySelect.value;
                setCustomInput(cityCustom, false, false);
            }

            function populateStates(selectedState = '') {
                stateSelect.innerHTML = '';
                addOption(stateSelect, '', 'Select state');

                states
                    .filter((item) => !countrySelect.value || item.country_id === countrySelect.value)
                    .forEach((item) => addOption(stateSelect, item.state, item.state));

                addOption(stateSelect, customValue, 'Add new state');

                stateSelect.value = optionExists(stateSelect, selectedState) ? selectedState : (selectedState ? customValue : '');
                stateCustom.value = selectedState || '';
                syncStateValue();
            }

            function populateCities(selectedCity = '') {
                const selectedState = stateValue.value;
                citySelect.innerHTML = '';
                addOption(citySelect, '', 'Select city');

                cities
                    .filter((city) => (!countrySelect.value || city.country_id === countrySelect.value) && (!selectedState || city.state === selectedState))
                    .forEach((city) => addOption(citySelect, city.city_name, city.city_name));

                addOption(citySelect, customValue, 'Add new city');

                citySelect.value = optionExists(citySelect, selectedCity) ? selectedCity : (selectedCity ? customValue : '');
                cityCustom.value = selectedCity || '';
                syncCityValue();
            }

            populateStates(stateSelect.dataset.selectedState || '');
            populateCities(citySelect.dataset.selectedCity || '');

            countrySelect.addEventListener('change', function () {
                populateStates();
                populateCities();
            });

            stateSelect.addEventListener('change', function () {
                syncStateValue();
                populateCities();
            });

            stateCustom.addEventListener('input', function () {
                syncStateValue();
                populateCities(cityValue.value);
            });

            citySelect.addEventListener('change', syncCityValue);
            cityCustom.addEventListener('input', syncCityValue);
        })();
    </script>
@endpush
