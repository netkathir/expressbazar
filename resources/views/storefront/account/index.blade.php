@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <div class="sf-info-card">
                        <h3 class="mb-3">My Account</h3>
                        <dl class="sf-specs">
                            <dt>Name</dt><dd>{{ $user->name }}</dd>
                            <dt>Email</dt><dd>{{ $user->email }}</dd>
                            <dt>Phone</dt><dd>{{ $user->phone ?: '-' }}</dd>
                            <dt>Status</dt><dd>{{ ucfirst($user->status) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="col-12 col-xl-8">
                    <div class="sf-info-card mb-4">
                        <h4 class="mb-3">Recent orders</h4>
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('storefront.orders.index') }}" class="btn btn-outline-dark rounded-pill btn-sm">View all orders</a>
                        </div>
                        <div class="d-grid gap-3">
                            @forelse ($orders as $order)
                                @php($latestPayment = $order->payments->last())
                                <div class="sf-sidepanel p-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-semibold">{{ $order->order_number }}</div>
                                            <div class="small text-secondary">{{ $order->vendor?->vendor_name ?? 'Store order' }}</div>
                                            <div class="small text-secondary">Placed on {{ optional($order->placed_at)->format('d M Y, h:i A') }}</div>
                                        </div>
                                        <div class="text-end">
                                            <div class="fw-semibold">₹{{ number_format((float) $order->total_amount, 0) }}</div>
                                            <span class="badge rounded-pill text-bg-light">{{ ucfirst($order->order_status) }}</span>
                                            <span class="badge rounded-pill text-bg-warning">{{ ucfirst($latestPayment?->status ?? $order->payment_status) }}</span>
                                            <div class="mt-2">
                                                <a href="{{ route('storefront.orders.show', $order) }}" class="btn btn-sm btn-outline-dark rounded-pill">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="sf-empty-state">No orders yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="sf-info-card mb-4">
                        <h4 class="mb-3">Saved addresses</h4>
                        <div class="d-grid gap-3">
                            @forelse ($addresses as $address)
                                <div class="sf-sidepanel p-3">
                                    <div class="d-flex justify-content-between gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ $address->label ?: $address->recipient_name }}</div>
                                            <div class="small text-secondary">{{ $address->address_line_1 }}, {{ $address->city?->city_name }}</div>
                                            <div class="small text-secondary">{{ $address->zone?->zone_name ?? '-' }} | {{ $address->postcode }}</div>
                                        </div>
                                        <form method="POST" action="{{ route('storefront.addresses.destroy', $address) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="sf-empty-state">No addresses saved yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="sf-info-card">
                        <h4 class="mb-3">Add address</h4>
                        <form method="POST" action="{{ route('storefront.addresses.store') }}" class="row g-3">
                            @csrf
                            <div class="col-12 col-md-6">
                                <label class="form-label">Label</label>
                                <input type="text" name="label" class="form-control" placeholder="Home / Work">
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
                                <input type="text" name="postcode" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address line 1</label>
                                <input type="text" name="address_line_1" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Address line 2</label>
                                <input type="text" name="address_line_2" class="form-control">
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Country</label>
                                <select name="country_id" class="form-select js-country-select" required>
                                    <option value="">Choose country</option>
                                    @foreach (($countries ?? \App\Models\Country::where('status', 'active')->orderBy('country_name')->get()) as $country)
                                        <option value="{{ $country->id }}">{{ $country->country_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">City</label>
                                <select name="city_id" class="form-select js-city-select" required>
                                    <option value="">Choose city</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label">Zone</label>
                                <select name="zone_id" class="form-select js-zone-select">
                                    <option value="">Optional exact zone</option>
                                </select>
                            </div>
                            <div class="col-12 form-check ms-3">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="defaultAddress">
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
