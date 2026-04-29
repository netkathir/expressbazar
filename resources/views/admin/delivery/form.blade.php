@extends('layouts.admin')

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Delivery Config' : 'Edit Delivery Config' }}</h1>
                </div>
                <a href="{{ route('admin.delivery.index') }}" class="btn btn-outline-secondary">Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.delivery.store') : route('admin.delivery.update', $config) }}" class="row g-3" id="delivery-form"
                  data-city-url="{{ route('admin.vendors.cities') }}"
                  data-zone-url="{{ route('admin.vendors.zones') }}"
                  data-selected-country="{{ old('country_id', $config->country_id) }}"
                  data-selected-city="{{ old('city_id', $config->city_id) }}"
                  data-selected-zone="{{ old('zone_id', $config->zone_id) }}">
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-4">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select" required>
                        <option value="">Select country</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) old('country_id', $config->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <select name="city_id" class="form-select" required>
                        <option value="">Select city</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Zone</label>
                    <select name="zone_id" class="form-select" required>
                        <option value="">Select zone</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Delivery Available</label>
                    <div class="form-check mt-2">
                        <input type="checkbox" name="delivery_available" value="1" class="form-check-input" id="deliveryAvailable" @checked(old('delivery_available', $config->delivery_available))>
                        <label class="form-check-label" for="deliveryAvailable">Yes</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Delivery Charge</label>
                    <input type="number" step="0.01" min="0" name="delivery_charge" value="{{ old('delivery_charge', $config->delivery_charge) }}" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $config->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $config->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Config' : 'Update Config' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('delivery-form');
            const countrySelect = form?.querySelector('select[name="country_id"]');
            const citySelect = form?.querySelector('select[name="city_id"]');
            const zoneSelect = form?.querySelector('select[name="zone_id"]');

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
                citySelect.innerHTML = '<option value="">Select city</option>';
                zoneSelect.innerHTML = '<option value="">Select zone</option>';

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
                zoneSelect.innerHTML = '<option value="">Select zone</option>';

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
