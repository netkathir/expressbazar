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
                    <label class="form-label">Vendor Name</label>
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
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $vendor->email) }}" class="form-control" required>
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
                    <label class="form-label">Inventory Mode</label>
                    <select name="inventory_mode" class="form-select" required>
                        <option value="" disabled hidden>Choose mode</option>
                        <option value="internal" @selected(old('inventory_mode', $vendor->inventory_mode ?: 'internal') === 'internal')>Internal</option>
                        <option value="epos" @selected(old('inventory_mode', $vendor->inventory_mode) === 'epos')>EPOS</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $vendor->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $vendor->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Panel Role</label>
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
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select" required id="countryId">
                        <option value="" disabled hidden>Choose country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $vendor->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <select name="city_id" class="form-select" required id="cityId">
                        <option value="" disabled hidden>Choose city</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Region / Zone</label>
                    <select name="region_zone_id" class="form-select" required id="zoneId">
                        <option value="" disabled hidden>Choose zone</option>
                    </select>
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

                        async function loadCities(countryId, cityId = '') {
                            citySelect.innerHTML = '<option value="" disabled hidden>Choose city</option>';
                            zoneSelect.innerHTML = '<option value="" disabled hidden>Choose zone</option>';

                            if (!countryId) {
                                return;
                            }

                            const response = await fetch(`${cityUrl}?country_id=${encodeURIComponent(countryId)}`, {
                                headers: { 'Accept': 'application/json' },
                            });

                            if (!response.ok) {
                                return;
                            }

                            const payload = await response.json();
                            (payload.data || []).forEach((item) => {
                                citySelect.appendChild(createOption(String(item.id), item.city_name, String(item.id) === String(cityId)));
                            });
                        }

                        async function loadZones(countryId, cityId, zoneId = '') {
                            zoneSelect.innerHTML = '<option value="" disabled hidden>Choose zone</option>';

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
                            (payload.data || []).forEach((item) => {
                                zoneSelect.appendChild(createOption(String(item.id), item.zone_name, String(item.id) === String(zoneId)));
                            });
                        }

                        countrySelect.addEventListener('change', async () => {
                            const countryId = countrySelect.value;
                            citySelect.value = '';
                            zoneSelect.value = '';
                            await loadCities(countryId);
                        });

                        citySelect.addEventListener('change', async () => {
                            const countryId = countrySelect.value;
                            const cityId = citySelect.value;
                            zoneSelect.value = '';
                            await loadZones(countryId, cityId);
                        });

                        (async () => {
                            await loadCities(selectedCountry, selectedCity);
                            await loadZones(selectedCountry, selectedCity, selectedZone);
                        })();
                    })();
                </script>
            @endpush
@endsection
