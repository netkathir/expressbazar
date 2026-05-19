@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">City Management</h1>
            </div>
            @canRoute('admin.cities.create')
                <a href="{{ route('admin.cities.create') }}" class="btn btn-primary">Add City</a>
            @endcanRoute
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET" data-location-filter>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="City name or code">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country</label>
                    <select name="country_id" class="form-select" data-country-filter>
                        <option value="">All</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->id }}" @selected((string) request('country_id') === (string) $country->id)>{{ $country->country_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-none" aria-hidden="true">
                    <label class="form-label">State</label>
                    <select name="state" class="form-select" data-state-filter data-selected-state="{{ request('state') }}">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">City</label>
                    <select name="city_id" class="form-select" data-city-filter data-selected-city="{{ request('city_id') }}">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.cities.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>City Name</th>
                        <th>Country</th>
                        <th>Code</th>
                        <th>Status</th>
                        <th>Zones</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($cities as $city)
                        <tr>
                            <td class="fw-semibold">{{ $city->city_name }}</td>
                            <td>{{ $city->country?->country_name }}</td>
                            <td>{{ $city->city_code ?: '-' }}</td>
                            <td><span class="badge text-bg-{{ $city->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($city->status) }}</span></td>
                            <td>{{ $city->zones_count }}</td>
                            <td class="text-end">
                                @canRoute('admin.cities.edit')
                                    <a href="{{ route('admin.cities.edit', $city) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit city" title="Edit city">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                @endcanRoute
                                @canRoute('admin.cities.destroy', 'DELETE')
                                    <form action="{{ route('admin.cities.destroy', $city) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this city?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete city" title="Delete city">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                @endcanRoute
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No cities found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $cities->links() }}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const form = document.querySelector('[data-location-filter]');
            if (!form) {
                return;
            }

            const countrySelect = form.querySelector('[data-country-filter]');
            const stateSelect = form.querySelector('[data-state-filter]');
            const citySelect = form.querySelector('[data-city-filter]');
            const states = @json($stateOptions);
            const cities = @json($cityOptions);

            if (!countrySelect || !stateSelect || !citySelect) {
                return;
            }

            const selectedState = stateSelect.dataset.selectedState || '';
            const selectedCity = citySelect.dataset.selectedCity || '';

            function addOption(select, value, label) {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                select.appendChild(option);
            }

            function populateStates(countryId, state = '') {
                stateSelect.innerHTML = '';
                addOption(stateSelect, '', 'All');

                states
                    .filter((item) => !countryId || item.country_id === countryId)
                    .forEach((item) => addOption(stateSelect, item.state, item.state));

                stateSelect.value = Array.from(stateSelect.options).some((option) => option.value === state) ? state : '';
            }

            function populateCities(countryId, state = '', cityId = '') {
                citySelect.innerHTML = '';
                addOption(citySelect, '', 'All');

                cities
                    .filter((city) => (!countryId || city.country_id === countryId) && (!state || city.state === state))
                    .forEach((city) => addOption(citySelect, city.id, city.city_name));

                citySelect.value = Array.from(citySelect.options).some((option) => option.value === cityId) ? cityId : '';
            }

            populateStates(countrySelect.value, selectedState);
            populateCities(countrySelect.value, stateSelect.value, selectedCity);

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
