@extends('layouts.storefront')

@section('content')
    @php
        $addressPrefill = $addressPrefill ?? [];
        $prefillCountryId = old('country_id', $addressPrefill['country_id'] ?? '');
        $prefillCityId = old('city_id', $addressPrefill['city_id'] ?? '');
        $prefillZoneId = old('zone_id', $addressPrefill['zone_id'] ?? '');
    @endphp
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">
                <a href="{{ route('user.home') }}">Home</a>
                <span>&rsaquo;</span>
                <a href="{{ route('storefront.account') }}">My Account</a>
                <span>&rsaquo;</span>
                Contact Address
            </nav>

            <div class="row justify-content-center">
                <div class="col-12 col-xxl-8">
                    <div class="sf-info-card mb-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                            <h3 class="mb-0">Saved Addresses</h3>
                            <a href="{{ route('storefront.account') }}" class="btn btn-outline-dark rounded-pill btn-sm">My Account</a>
                        </div>
                        <div class="d-grid gap-3" data-address-list>
                            @forelse ($addresses as $address)
                                <div class="sf-sidepanel p-3 {{ $loop->index >= 3 ? 'd-none' : '' }}" data-address-item data-extra-address="{{ $loop->index >= 3 ? 'true' : 'false' }}">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $address->label ?: $address->recipient_name }}</div>
                                            <div class="small text-secondary">{{ $address->address_line_1 }}, {{ $address->city?->city_name }}</div>
                                            <div class="small text-secondary">{{ $address->zone?->zone_name ?? '-' }}</div>
                                            <div class="small text-secondary">Postcode: {{ $address->postcode }}</div>
                                        </div>
                                        <div class="d-flex flex-column gap-2">
                                            <a href="{{ route('storefront.addresses.edit', $address) }}" class="btn btn-sm btn-outline-dark">Edit</a>
                                            <form method="POST" action="{{ route('storefront.addresses.destroy', $address) }}" class="js-address-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger w-100">Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="sf-empty-state">No addresses saved yet.</div>
                            @endforelse
                        </div>
                        @if ($addresses->count() > 3)
                            <div class="mt-3 text-center">
                                <button type="button" class="btn btn-outline-dark rounded-pill btn-sm px-4" data-address-view-more>View more addresses</button>
                            </div>
                        @endif
                    </div>

                    <div class="sf-info-card">
                        <h3 class="mb-3">Add Address</h3>
                        <form method="POST" action="{{ route('storefront.addresses.store') }}" class="row g-3 sf-address-form">
                            @csrf
                            <div class="col-12 col-md-6">
                                <label class="form-label">Label</label>
                                <input type="text" name="label" class="form-control" placeholder="Home / Work" value="{{ old('label') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Recipient name</label>
                                <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name', $user->name) }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Postcode</label>
                                <input type="text" name="postcode" class="form-control" value="{{ old('postcode', $addressPrefill['postcode'] ?? '') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address line 1</label>
                                <div class="location-autocomplete-wrapper">
                                    <input
                                        type="text"
                                        name="address_line_1"
                                        class="form-control js-address-location-search"
                                        placeholder="Start typing an address..."
                                        value="{{ old('address_line_1', $addressPrefill['address_line_1'] ?? '') }}"
                                        autocomplete="off"
                                        role="combobox"
                                        aria-expanded="false"
                                        required
                                    >
                                    <div class="js-address-location-suggestion-box" role="listbox" hidden></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address line 2</label>
                                <input type="text" name="address_line_2" class="form-control" value="{{ old('address_line_2') }}">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Country</label>
                                <select name="country_id" class="form-select js-country-select" required>
                                    <option value="">Choose country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) $prefillCountryId === (string) $country->id)>{{ $country->country_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">City</label>
                                <select name="city_id" class="form-select js-city-select" required>
                                    <option value="">Choose city</option>
                                    @foreach (($prefillCities ?? collect()) as $city)
                                        <option value="{{ $city->id }}" @selected((string) $prefillCityId === (string) $city->id)>{{ $city->city_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Zone</label>
                                <select name="zone_id" class="form-select js-zone-select">
                                    <option value="">Optional exact zone</option>
                                    @foreach (($prefillZones ?? collect()) as $zone)
                                        <option value="{{ $zone->id }}" @selected((string) $prefillZoneId === (string) $zone->id)>{{ $zone->zone_name }}{{ $zone->zone_code ? ' ('.$zone->zone_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 form-check ms-3">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defaultAddress" @checked(old('is_default'))>
                                <label class="form-check-label" for="defaultAddress">Set as default address</label>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-danger rounded-pill">Save Address</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script>
        document.querySelector('[data-address-view-more]')?.addEventListener('click', function () {
            const items = document.querySelectorAll('[data-address-item][data-extra-address="true"]');
            const isExpanding = Array.from(items).some((item) => item.classList.contains('d-none'));

            items.forEach((item) => item.classList.toggle('d-none', !isExpanding));
            this.textContent = isExpanding ? 'Show fewer addresses' : 'View more addresses';
        });
    </script>
@endpush
