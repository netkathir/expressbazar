@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <div class="row justify-content-center">
                <div class="col-12 col-xxl-8">
                    <div class="sf-info-card p-4 p-md-5">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                            <div>
                                <h3 class="mb-1">Edit Address</h3>
                                <p class="text-secondary mb-0">Update this delivery address while keeping the rest of your account unchanged.</p>
                            </div>
                            <a href="{{ route('storefront.account') }}" class="btn btn-outline-dark rounded-pill btn-sm">Back to Account</a>
                        </div>

                        <form method="POST" action="{{ route('storefront.addresses.update', $address) }}" class="row g-3">
                            @csrf
                            @method('PUT')

                            <div class="col-12 col-md-6">
                                <label class="form-label">Label</label>
                                <input type="text" name="label" class="form-control" placeholder="Home / Work" value="{{ old('label', $address->label) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Recipient name</label>
                                <input type="text" name="recipient_name" class="form-control" value="{{ old('recipient_name', $address->recipient_name) }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', $address->phone) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Postcode</label>
                                <input type="text" name="postcode" class="form-control" value="{{ old('postcode', $address->postcode) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address line 1</label>
                                <input type="text" name="address_line_1" class="form-control" value="{{ old('address_line_1', $address->address_line_1) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address line 2</label>
                                <input type="text" name="address_line_2" class="form-control" value="{{ old('address_line_2', $address->address_line_2) }}">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Country</label>
                                <select name="country_id" class="form-select js-country-select" required>
                                    <option value="">Choose country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->id }}" @selected((string) old('country_id', $address->country_id) === (string) $country->id)>{{ $country->country_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">City</label>
                                <select name="city_id" class="form-select js-city-select" required>
                                    <option value="">Choose city</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}" @selected((string) old('city_id', $address->city_id) === (string) $city->id)>{{ $city->city_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Zone</label>
                                <select name="zone_id" class="form-select js-zone-select">
                                    <option value="">Optional exact zone</option>
                                    @foreach ($zones as $zone)
                                        <option value="{{ $zone->id }}" @selected((string) old('zone_id', $address->zone_id) === (string) $zone->id)>{{ $zone->zone_name }}{{ $zone->zone_code ? ' ('.$zone->zone_code.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 form-check ms-3">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defaultAddress" @checked(old('is_default', $address->is_default))>
                                <label class="form-check-label" for="defaultAddress">Set as default address</label>
                            </div>
                            <div class="col-12 d-grid d-md-flex justify-content-md-end gap-2 pt-2">
                                <a href="{{ route('storefront.account') }}" class="btn btn-light rounded-pill px-4">Cancel</a>
                                <button class="btn btn-danger rounded-pill px-4">Save Address</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
