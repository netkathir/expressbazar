@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Vendor' : 'Edit Vendor' }}</h1>
                </div>
                <a href="{{ route('admin.vendors.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form
                method="POST"
                action="{{ $mode === 'create' ? route('admin.vendors.store') : route('admin.vendors.update', $vendor) }}"
                class="row g-3"
                id="vendorForm"
                data-dirty-check
                data-state-url="{{ route('admin.vendors.states') }}"
                data-city-url="{{ route('admin.vendors.cities') }}"
                data-zone-url="{{ route('admin.vendors.zones') }}"
                data-selected-country="{{ old('country_id', $vendor->country_id) }}"
                data-selected-state="{{ old('state', $vendor->city?->state) }}"
                data-selected-city="{{ old('city_id', $vendor->city_id) }}"
                data-selected-zone="{{ old('region_zone_id', $vendor->region_zone_id) }}"
            >
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Vendor Name <span class="text-danger">*</span></label>
                    <input
                        type="text"
                        name="vendor_name"
                        value="{{ old('vendor_name', $vendor->vendor_name) }}"
                        class="form-control"
                        required
                        pattern="^(?=.*[A-Za-z0-9])[A-Za-z0-9\s&.,'()\-\/]+$"
                        minlength="2"
                        title="Use letters, numbers, spaces, and common business symbols only."
                    >
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $vendor->email) }}"
                        class="form-control"
                        required
                        pattern="^[^\s@]+@[^\s@]+\.[^\s@]+$"
                        title="Enter a valid email address with domain and extension."
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone</label>
                    <input
                        type="tel"
                        name="phone"
                        value="{{ old('phone', $vendor->phone) }}"
                        class="form-control"
                        inputmode="numeric"
                        pattern="[0-9]{10,15}"
                        minlength="10"
                        maxlength="15"
                        title="Enter 10 to 15 digits only."
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label">Pincode</label>
                    <input
                        type="text"
                        name="pincode"
                        value="{{ old('pincode', $vendor->pincode) }}"
                        class="form-control"
                        pattern="[A-Za-z0-9 ]{3,12}"
                        minlength="3"
                        maxlength="12"
                        title="Enter 3 to 12 letters or numbers."
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label">Inventory Mode <span class="text-danger">*</span></label>
                    <select name="inventory_mode" class="form-select" required>
                        <option value="" disabled hidden>Choose mode</option>
                        <option value="internal" @selected(old('inventory_mode', $vendor->inventory_mode) === 'internal')>Internal</option>
                        <option value="epos" @selected(old('inventory_mode', $vendor->inventory_mode) === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="" disabled hidden>Select status</option>
                        <option value="active" @selected(old('status', $vendor->status) === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $vendor->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Panel Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="" disabled hidden>Choose role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->role_name }}" @selected(old('role', $vendor->role) === $role->role_name)>{{ \Illuminate\Support\Str::headline($role->role_name) }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Controls vendor panel permissions.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" value="{{ old('address', $vendor->address) }}" class="form-control" id="vendorAddress" autocomplete="off">
                    @if (config('services.google_maps.key'))
                        <div class="form-text">Start typing and choose an address to auto-fill location fields.</div>
                    @endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">Country <span class="text-danger">*</span></label>
                    <div class="select-field-placeholder-wrap">
                        <select name="country_id" class="form-select" required id="countryId" data-placeholder-target="country">
                            <option value="" disabled hidden>Select country</option>
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected((string) old('country_id', $vendor->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                            @endforeach
                        </select>
                        <span class="select-field-placeholder" data-select-placeholder>Select country</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <div class="select-field-placeholder-wrap">
                        <select name="state" class="form-select" id="stateId" data-placeholder-target="state"></select>
                        <span class="select-field-placeholder" data-select-placeholder>Select state</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <div class="select-field-placeholder-wrap">
                        <select name="city_id" class="form-select" required id="cityId" data-placeholder-target="city"></select>
                        <span class="select-field-placeholder" data-select-placeholder>Select city</span>
                    </div>
                </div>
                <div class="col-md-4 d-none" aria-hidden="true">
                    <label class="form-label">Region / Zone <span class="text-danger">*</span></label>
                    <div class="select-field-placeholder-wrap">
                        <select name="region_zone_id" class="form-select" id="zoneId" data-placeholder-target="zone"></select>
                        <span class="select-field-placeholder" data-select-placeholder>Select zone</span>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">API URL</label>
                    <input type="url" name="api_url" value="{{ old('api_url', $vendor->api_url) }}" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">API Key</label>
                    <input type="text" name="api_key" value="{{ old('api_key', $vendor->api_key) }}" class="form-control">
                </div>
                <div class="col-12">
                    <label class="form-label">Credentials</label>
                    <textarea name="credentials" class="form-control" rows="4">{{ old('credentials', $vendor->credentials) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Vendor' : 'Update Vendor' }}</button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
                <script>
                    (function () {
                        const form = document.getElementById('vendorForm');
                        const countrySelect = document.getElementById('countryId');
                        const stateSelect = document.getElementById('stateId');
                        const citySelect = document.getElementById('cityId');
                        const zoneSelect = document.getElementById('zoneId');

                        if (!form || !countrySelect || !stateSelect || !citySelect || !zoneSelect) {
                            return;
                        }

                        zoneSelect.required = false;

                        const stateUrl = form.dataset.stateUrl;
                        const cityUrl = form.dataset.cityUrl;
                        const zoneUrl = form.dataset.zoneUrl;
                        const addressInput = document.getElementById('vendorAddress');
                        const pincodeInput = form.querySelector('[name="pincode"]');
                        const selectedCountry = form.dataset.selectedCountry || '';
                        const selectedState = form.dataset.selectedState || '';
                        const selectedCity = form.dataset.selectedCity || '';
                        const selectedZone = form.dataset.selectedZone || '';

                        const createOption = (value, label, selected = false) => {
                            const option = document.createElement('option');
                            option.value = value;
                            option.textContent = label;
                            if (selected) {
                                option.selected = true;
                            }
                            return option;
                        };

                        const createPlaceholderOption = (label) => {
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = label;
                            option.disabled = true;
                            option.hidden = true;
                            option.selected = true;
                            return option;
                        };

                        const syncPlaceholder = (select) => {
                            const placeholder = select.closest('.select-field-placeholder-wrap')?.querySelector('[data-select-placeholder]');
                            if (placeholder) {
                                const selectedText = select.selectedOptions[0]?.textContent?.trim() || '';
                                placeholder.hidden = Boolean(select.value || selectedText);
                            }
                        };

                        const syncFieldState = (control) => {
                            if (!control) {
                                return;
                            }

                            syncPlaceholder(control);
                            control.dispatchEvent(new Event('input', { bubbles: true }));
                        };

                        async function loadStates(countryId, state = '') {
                            stateSelect.innerHTML = '';
                            syncPlaceholder(stateSelect);

                            if (!countryId) {
                                return '';
                            }

                            const response = await fetch(`${stateUrl}?country_id=${encodeURIComponent(countryId)}`, {
                                headers: { 'Accept': 'application/json' },
                            });

                            if (!response.ok) {
                                return '';
                            }

                            const payload = await response.json();
                            const states = payload.data || [];

                            if (states.length !== 1) {
                                stateSelect.appendChild(createPlaceholderOption('Select state'));
                            }

                            states.forEach((item) => {
                                stateSelect.appendChild(createOption(item.state, item.state, item.state === state));
                            });

                            const resolvedState = state || (states.length === 1 ? states[0].state : '');
                            if (resolvedState) {
                                stateSelect.value = resolvedState;
                            }

                            if (!resolvedState && states.length > 1) {
                                stateSelect.value = '';
                            }

                            syncFieldState(stateSelect);
                            return stateSelect.value || '';
                        }

                        async function loadCities(countryId, state = '', cityId = '') {
                            citySelect.innerHTML = '';
                            zoneSelect.innerHTML = '';
                            syncPlaceholder(citySelect);
                            syncPlaceholder(zoneSelect);

                            if (!countryId) {
                                return '';
                            }

                            const response = await fetch(`${cityUrl}?country_id=${encodeURIComponent(countryId)}&state=${encodeURIComponent(state)}`, {
                                headers: { 'Accept': 'application/json' },
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            const cities = payload.data || [];

                            if (cities.length !== 1) {
                                citySelect.appendChild(createPlaceholderOption('Select city'));
                            }

                            cities.forEach((item) => {
                                citySelect.appendChild(createOption(String(item.id), item.city_name, String(item.id) === String(cityId)));
                            });

                            const resolvedCityId = cityId || (cities.length === 1 ? String(cities[0].id) : '');
                            if (resolvedCityId) {
                                citySelect.value = resolvedCityId;
                            }

                            if (!resolvedCityId && cities.length > 1) {
                                citySelect.value = '';
                            }

                            syncFieldState(citySelect);
                            return citySelect.value || '';
                        }

                        async function loadZones(countryId, cityId, zoneId = '') {
                            zoneSelect.innerHTML = '';
                            syncPlaceholder(zoneSelect);

                            if (!countryId || !cityId) {
                                return;
                            }

                            const response = await fetch(`${zoneUrl}?country_id=${encodeURIComponent(countryId)}&city_id=${encodeURIComponent(cityId)}`, {
                                headers: { 'Accept': 'application/json' },
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            const zones = payload.data || [];

                            if (zones.length !== 1) {
                                zoneSelect.appendChild(createPlaceholderOption('Select zone'));
                            }

                            zones.forEach((item) => {
                                const option = createOption(String(item.id), item.zone_name, String(item.id) === String(zoneId));
                                option.dataset.zoneCode = item.zone_code || '';
                                zoneSelect.appendChild(option);
                            });

                            const resolvedZoneId = zoneId || (zones.length ? String(zones[0].id) : '');
                            if (resolvedZoneId) {
                                zoneSelect.value = resolvedZoneId;
                            }

                            if (!resolvedZoneId && zones.length > 1) {
                                zoneSelect.value = '';
                            }

                            syncFieldState(zoneSelect);
                        }

                        const normalizeText = (value) => String(value || '')
                            .toLowerCase()
                            .replace(/[^a-z0-9]+/g, ' ')
                            .trim();

                        const findOptionByText = (select, candidates) => {
                            const normalizedCandidates = candidates.map(normalizeText).filter(Boolean);

                            if (normalizedCandidates.length === 0) {
                                return null;
                            }

                            return Array.from(select.options).find((option) => {
                                const text = normalizeText(option.textContent);
                                return normalizedCandidates.some((candidate) => {
                                    if (candidate.length <= 3) {
                                        return text === candidate;
                                    }

                                    return text === candidate || text.includes(candidate) || candidate.includes(text);
                                });
                            }) || null;
                        };

                        const getAddressPart = (components, type, property = 'long_name') => {
                            const component = components.find((item) => item.types.includes(type));
                            return component ? component[property] : '';
                        };

                        const selectZoneFromPostcode = async (countryId, cityId, postcode = '') => {
                            await loadZones(countryId, cityId);

                            const postcodePrefix = postcode.trim().split(/\s+/)[0]?.toUpperCase() || '';
                            if (!postcodePrefix) {
                                return;
                            }

                            const zoneOption = Array.from(zoneSelect.options).find((option) => {
                                const text = option.textContent.toUpperCase();
                                const code = (option.dataset.zoneCode || '').toUpperCase();
                                return option.value && (text.includes(postcodePrefix) || code === postcodePrefix || postcodePrefix.startsWith(code));
                            });

                            if (zoneOption) {
                                zoneSelect.value = zoneOption.value;
                                syncFieldState(zoneSelect);
                            }
                        };

                        const applyPlaceToVendorLocation = async (place) => {
                            if (!place || !place.geometry) {
                                return;
                            }

                            const components = place.address_components || [];
                            const formattedAddress = place.formatted_address || addressInput?.value || '';
                            const countryName = getAddressPart(components, 'country');
                            const stateName = getAddressPart(components, 'administrative_area_level_1');
                            const postcode = getAddressPart(components, 'postal_code');
                            const cityCandidates = [
                                getAddressPart(components, 'locality'),
                                getAddressPart(components, 'postal_town'),
                                getAddressPart(components, 'administrative_area_level_2'),
                                getAddressPart(components, 'sublocality_level_1'),
                            ].filter(Boolean);

                            if (addressInput && formattedAddress) {
                                addressInput.value = formattedAddress;
                                addressInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }

                            const compactPostcode = postcode.replace(/[^A-Za-z0-9 ]/g, '').trim();
                            if (pincodeInput && /^[A-Za-z0-9 ]{3,12}$/.test(compactPostcode)) {
                                pincodeInput.value = compactPostcode;
                                pincodeInput.dispatchEvent(new Event('input', { bubbles: true }));
                            }

                            const countryOption = findOptionByText(countrySelect, [countryName]);
                            if (!countryOption) {
                                return;
                            }

                            countrySelect.value = countryOption.value;
                            syncFieldState(countrySelect);

                            const matchedState = await loadStates(countrySelect.value, stateName);
                            await loadCities(countrySelect.value, matchedState);

                            const cityOption = findOptionByText(citySelect, cityCandidates);
                            if (!cityOption) {
                                syncPlaceholder(citySelect);
                                zoneSelect.innerHTML = '';
                                syncPlaceholder(zoneSelect);
                                return;
                            }

                            citySelect.value = cityOption.value;
                            syncFieldState(citySelect);
                            await selectZoneFromPostcode(countrySelect.value, citySelect.value, postcode);
                        };

                        window.initAdminVendorAddressAutocomplete = function () {
                            if (!addressInput || !window.google?.maps?.places?.Autocomplete) {
                                return;
                            }

                            const autocomplete = new google.maps.places.Autocomplete(addressInput, {
                                types: ['address'],
                                fields: ['formatted_address', 'geometry', 'address_components', 'name'],
                            });

                            autocomplete.addListener('place_changed', function () {
                                applyPlaceToVendorLocation(autocomplete.getPlace());
                            });
                        };

                        countrySelect.addEventListener('change', async () => {
                            const countryId = countrySelect.value;
                            stateSelect.value = '';
                            citySelect.value = '';
                            zoneSelect.value = '';
                            syncPlaceholder(countrySelect);
                            syncPlaceholder(stateSelect);
                            syncPlaceholder(citySelect);
                            syncPlaceholder(zoneSelect);
                            const resolvedState = await loadStates(countryId);
                            const selectedCityId = await loadCities(countryId, resolvedState);
                            if (selectedCityId) {
                                await loadZones(countryId, selectedCityId);
                            }
                        });

                        stateSelect.addEventListener('change', async () => {
                            const countryId = countrySelect.value;
                            const state = stateSelect.value;
                            citySelect.value = '';
                            zoneSelect.value = '';
                            syncPlaceholder(stateSelect);
                            syncPlaceholder(citySelect);
                            syncPlaceholder(zoneSelect);
                            const selectedCityId = await loadCities(countryId, state);
                            if (selectedCityId) {
                                await loadZones(countryId, selectedCityId);
                            }
                        });

                        citySelect.addEventListener('change', async () => {
                            const countryId = countrySelect.value;
                            const cityId = citySelect.value;
                            zoneSelect.value = '';
                            syncPlaceholder(citySelect);
                            syncPlaceholder(zoneSelect);
                            await loadZones(countryId, cityId);
                        });

                        zoneSelect.addEventListener('change', () => {
                            syncPlaceholder(zoneSelect);
                        });

                        zoneSelect.addEventListener('input', () => {
                            syncPlaceholder(zoneSelect);
                        });

                        (async () => {
                            const initialCountry = selectedCountry || '';
                            countrySelect.value = initialCountry;
                            syncPlaceholder(countrySelect);

                            if (initialCountry) {
                                const initialState = await loadStates(initialCountry, selectedState);
                                const initialCity = await loadCities(initialCountry, initialState, selectedCity);
                                await loadZones(initialCountry, initialCity, selectedZone);
                                syncPlaceholder(stateSelect);
                                syncPlaceholder(citySelect);
                                syncPlaceholder(zoneSelect);
                            } else {
                                stateSelect.innerHTML = '';
                                citySelect.innerHTML = '';
                                zoneSelect.innerHTML = '';
                                syncPlaceholder(stateSelect);
                                syncPlaceholder(citySelect);
                                syncPlaceholder(zoneSelect);
                            }
                        })();
                    })();
                </script>
                @if (config('services.google_maps.key'))
                    <script
                        src="https://maps.googleapis.com/maps/api/js?key={{ rawurlencode(config('services.google_maps.key')) }}&libraries=places&callback=initAdminVendorAddressAutocomplete&loading=async"
                        async
                        defer
                    ></script>
                @endif
            @endpush
@endsection
