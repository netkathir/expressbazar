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
                data-city-url="{{ route('admin.vendors.cities') }}"
                data-zone-url="{{ route('admin.vendors.zones') }}"
                data-selected-country="{{ old('country_id', $vendor->country_id) }}"
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
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        minlength="6"
                        maxlength="6"
                        title="Enter exactly 6 digits."
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label">Inventory Mode <span class="text-danger">*</span></label>
                    <select name="inventory_mode" class="form-select" required>
                        <option value="" disabled hidden>Choose mode</option>
                        <option value="internal" @selected(old('inventory_mode', $vendor->inventory_mode ?: 'internal') === 'internal')>Internal</option>
                        <option value="epos" @selected(old('inventory_mode', $vendor->inventory_mode) === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $vendor->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $vendor->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Panel Role <span class="text-danger">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="" disabled hidden>Choose role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->role_name }}" @selected(old('role', $vendor->role ?: 'vendor') === $role->role_name)>{{ \Illuminate\Support\Str::headline($role->role_name) }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Controls vendor panel permissions.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3">{{ old('address', $vendor->address) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Country <span class="text-danger">*</span></label>
                    <div class="select-field-placeholder-wrap">
                        <select name="country_id" class="form-select" required id="countryId" data-placeholder-target="country">
                            @foreach ($countries as $country)
                                <option value="{{ $country->id }}" @selected((string) old('country_id', $vendor->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                            @endforeach
                        </select>
                        <span class="select-field-placeholder" data-select-placeholder>Select country</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City <span class="text-danger">*</span></label>
                    <div class="select-field-placeholder-wrap">
                        <select name="city_id" class="form-select" required id="cityId" data-placeholder-target="city"></select>
                        <span class="select-field-placeholder" data-select-placeholder>Select city</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Region / Zone <span class="text-danger">*</span></label>
                    <div class="select-field-placeholder-wrap">
                        <select name="region_zone_id" class="form-select" required id="zoneId" data-placeholder-target="zone"></select>
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
                @if ($mode === 'edit')
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="send_credentials" value="1" class="form-check-input" id="sendCredentials">
                            <label class="form-check-label" for="sendCredentials">Generate and email new vendor panel credentials</label>
                        </div>
                    </div>
                @endif
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
                        const citySelect = document.getElementById('cityId');
                        const zoneSelect = document.getElementById('zoneId');

                        if (!form || !countrySelect || !citySelect || !zoneSelect) {
                            return;
                        }

                        const cityUrl = form.dataset.cityUrl;
                        const zoneUrl = form.dataset.zoneUrl;
                        const selectedCountry = form.dataset.selectedCountry || '';
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
                                placeholder.hidden = Boolean(select.value);
                            }
                        };

                        async function loadCities(countryId, cityId = '') {
                            citySelect.innerHTML = '';
                            zoneSelect.innerHTML = '';
                            syncPlaceholder(citySelect);
                            syncPlaceholder(zoneSelect);

                            if (!countryId) {
                                return '';
                            }

                            const response = await fetch(`${cityUrl}?country_id=${encodeURIComponent(countryId)}`, {
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

                            syncPlaceholder(citySelect);
                            return resolvedCityId;
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
                                zoneSelect.appendChild(createOption(String(item.id), item.zone_name, String(item.id) === String(zoneId)));
                            });

                            const resolvedZoneId = zoneId || (zones.length === 1 ? String(zones[0].id) : '');
                            if (resolvedZoneId) {
                                zoneSelect.value = resolvedZoneId;
                            }

                            if (!resolvedZoneId && zones.length > 1) {
                                zoneSelect.value = '';
                            }

                            syncPlaceholder(zoneSelect);
                        }

                        countrySelect.addEventListener('change', async () => {
                            const countryId = countrySelect.value;
                            citySelect.value = '';
                            zoneSelect.value = '';
                            syncPlaceholder(countrySelect);
                            syncPlaceholder(citySelect);
                            syncPlaceholder(zoneSelect);
                            const selectedCityId = await loadCities(countryId);
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
                                const initialCity = await loadCities(initialCountry, selectedCity);
                                await loadZones(initialCountry, initialCity, selectedZone);
                                syncPlaceholder(citySelect);
                                syncPlaceholder(zoneSelect);
                            } else {
                                citySelect.innerHTML = '';
                                zoneSelect.innerHTML = '';
                                syncPlaceholder(citySelect);
                                syncPlaceholder(zoneSelect);
                            }
                        })();
                    })();
                </script>
            @endpush
@endsection
