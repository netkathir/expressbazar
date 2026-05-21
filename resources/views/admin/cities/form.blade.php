@extends('layouts.admin')

@push('head')
    <style>
        .city-autocomplete {
            position: relative;
        }

        .city-autocomplete-menu {
            position: absolute;
            z-index: 20;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            max-height: min(60vh, 520px);
            overflow-y: auto;
            background: #fff;
            border: 1px solid #d8dee9;
            border-radius: 0.375rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
        }

        .city-autocomplete-option {
            width: 100%;
            border: 0;
            background: transparent;
            padding: 0.65rem 0.85rem;
            text-align: left;
            color: #111827;
        }

        .city-autocomplete-option:hover,
        .city-autocomplete-option:focus {
            background: #eef7e7;
            outline: 0;
        }

        .city-autocomplete-empty {
            padding: 0.65rem 0.85rem;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add City' : 'Edit City' }}</h1>
                </div>
                <a href="{{ route('admin.cities.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.cities.store') : route('admin.cities.update', $city) }}" class="row g-3" data-dirty-check data-cities-url="{{ route('admin.cities.by-country', ['country_id' => '__COUNTRY_ID__']) }}">
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
                <div class="col-md-6 d-none" aria-hidden="true">
                    <label class="form-label">State / Province</label>
                    <select class="form-select" data-state-select data-selected-state="{{ old('state', $city->state) }}">
                        <option value="">Select state</option>
                    </select>
                    <input type="text" value="{{ old('state', $city->state) }}" class="form-control mt-2 d-none" pattern="^(?=.*[A-Za-z])[A-Za-z .'()-]+$" placeholder="Enter state / province" data-state-custom>
                    <input type="hidden" name="state" value="{{ old('state', $city->state) }}" data-state-value>
                </div>
                <div class="col-md-6">
                    <label class="form-label">City Name</label>
                    <div class="city-autocomplete" data-city-autocomplete>
                        <input
                            type="text"
                            name="city_name"
                            value="{{ old('city_name', $city->city_name) }}"
                            class="form-control"
                            required
                            pattern="^(?=.*[A-Za-z])[A-Za-z .'()-]+$"
                            placeholder="Type or select city"
                            autocomplete="off"
                            data-city-input
                        >
                        <div class="city-autocomplete-menu" data-city-suggestions hidden></div>
                    </div>
                    <div class="form-text" data-city-load-status>Select a country to load city suggestions.</div>
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
            const form = document.querySelector('[data-cities-url]');
            const countrySelect = document.querySelector('[data-country-select]');
            const stateSelect = document.querySelector('[data-state-select]');
            const stateCustom = document.querySelector('[data-state-custom]');
            const stateValue = document.querySelector('[data-state-value]');
            const cityAutocomplete = document.querySelector('[data-city-autocomplete]');
            const cityInput = document.querySelector('[data-city-input]');
            const citySuggestions = document.querySelector('[data-city-suggestions]');
            const cityLoadStatus = document.querySelector('[data-city-load-status]');
            const states = @json($stateOptions);
            const customValue = '__custom__';

            if (!form || !countrySelect || !stateSelect || !stateCustom || !stateValue || !cityAutocomplete || !cityInput || !citySuggestions || !cityLoadStatus) {
                return;
            }

            const citiesUrlTemplate = form.dataset.citiesUrl;
            const citiesByCountry = new Map();
            let cityRequestController = null;

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

            function setCityStatus(message) {
                cityLoadStatus.textContent = message;
            }

            function hideCitySuggestions() {
                citySuggestions.hidden = true;
            }

            function showCitySuggestions() {
                citySuggestions.hidden = false;
            }

            function selectedCountryCities() {
                return citiesByCountry.get(countrySelect.value) || [];
            }

            function renderCitySuggestions(cities) {
                const search = cityInput.value.trim().toLowerCase();
                citySuggestions.innerHTML = '';

                const uniqueCities = Array.from(
                    new Map(cities.map((city) => [city.city_name.toLowerCase(), city])).values()
                ).sort((first, second) => first.city_name.localeCompare(second.city_name));

                const filteredCities = uniqueCities.filter((city) => !search || city.city_name.toLowerCase().includes(search));

                if (filteredCities.length === 0) {
                    const empty = document.createElement('div');
                    empty.className = 'city-autocomplete-empty';
                    empty.textContent = cities.length ? 'No matching cities' : 'No cities available';
                    citySuggestions.appendChild(empty);
                    showCitySuggestions();
                    return;
                }

                filteredCities.forEach((city) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'city-autocomplete-option';
                    option.textContent = city.city_name;
                    option.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                        cityInput.value = city.city_name;
                        cityInput.classList.remove('is-invalid');
                        cityInput.removeAttribute('aria-invalid');
                        cityInput.dispatchEvent(new Event('input', { bubbles: true }));
                        hideCitySuggestions();
                    });
                    citySuggestions.appendChild(option);
                });

                showCitySuggestions();
            }

            async function populateCitySuggestions(clearCity = false) {
                const countryId = countrySelect.value;

                if (clearCity) {
                    cityInput.value = '';
                    cityInput.dispatchEvent(new Event('input', { bubbles: true }));
                }

                citySuggestions.innerHTML = '';

                if (!countryId) {
                    setCityStatus('Select a country to load city suggestions.');
                    hideCitySuggestions();
                    return;
                }

                if (citiesByCountry.has(countryId)) {
                    const cachedCities = citiesByCountry.get(countryId);
                    renderCitySuggestions(cachedCities);
                    setCityStatus(cachedCities.length ? `Showing all ${cachedCities.length} available cities.` : 'No cities available.');
                    hideCitySuggestions();
                    return;
                }

                cityRequestController?.abort();
                cityRequestController = new AbortController();
                setCityStatus('Loading city suggestions...');

                try {
                    const url = citiesUrlTemplate.replace('__COUNTRY_ID__', encodeURIComponent(countryId));
                    const response = await fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                        },
                        signal: cityRequestController.signal,
                    });

                    if (!response.ok) {
                        throw new Error('Unable to fetch cities');
                    }

                    const payload = await response.json();
                    const cities = payload.data || [];
                    citiesByCountry.set(countryId, cities);
                    renderCitySuggestions(cities);
                    setCityStatus(cities.length ? `Showing all ${cities.length} available cities.` : 'No cities available.');
                    hideCitySuggestions();
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    setCityStatus('Unable to fetch cities.');
                }
            }

            populateStates(stateSelect.dataset.selectedState || '');
            populateCitySuggestions(false);

            countrySelect.addEventListener('change', function () {
                populateStates();
                populateCitySuggestions(true);
            });

            stateSelect.addEventListener('change', function () {
                syncStateValue();
                if (countrySelect.value && citiesByCountry.has(countrySelect.value)) {
                    renderCitySuggestions(citiesByCountry.get(countrySelect.value));
                    hideCitySuggestions();
                }
            });

            stateCustom.addEventListener('input', function () {
                syncStateValue();
                if (countrySelect.value && citiesByCountry.has(countrySelect.value)) {
                    renderCitySuggestions(citiesByCountry.get(countrySelect.value));
                    hideCitySuggestions();
                }
            });

            cityInput.addEventListener('focus', function () {
                if (countrySelect.value && citiesByCountry.has(countrySelect.value)) {
                    renderCitySuggestions(selectedCountryCities());
                }
            });

            cityInput.addEventListener('click', function () {
                if (countrySelect.value && citiesByCountry.has(countrySelect.value)) {
                    renderCitySuggestions(selectedCountryCities());
                }
            });

            cityInput.addEventListener('input', function () {
                if (countrySelect.value && citiesByCountry.has(countrySelect.value)) {
                    renderCitySuggestions(selectedCountryCities());
                }
            });

            document.addEventListener('mousedown', function (event) {
                if (!cityAutocomplete.contains(event.target)) {
                    hideCitySuggestions();
                }
            });

            cityInput.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    hideCitySuggestions();
                }
            });
        })();
    </script>
@endpush
