@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <div class="row g-4">
                <div class="col-12 col-xl-4">
                    <div class="sf-info-card">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <h3 class="mb-0">My Account</h3>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                <a href="{{ route('storefront.profile.edit') }}" class="btn btn-outline-dark rounded-pill btn-sm">Edit Profile</a>
                                <form action="{{ route('storefront.logout') }}" method="POST" class="d-inline js-logout-form">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger rounded-pill btn-sm">
                                        <i class="ti ti-logout me-1"></i>Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                        <dl class="sf-specs">
                            <dt>Name</dt><dd>{{ $user->name }}</dd>
                            <dt>Email</dt><dd class="text-break">{{ $user->email }}</dd>
                            <dt>Phone</dt><dd>{{ $user->phone ?: '-' }}</dd>
                            <dt>Status</dt><dd>{{ ucfirst($user->status) }}</dd>
                        </dl>
                    </div>
                </div>
                <div class="col-12 col-xl-8">
                    <div class="sf-info-card mb-4" id="wishlist">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <h4 class="mb-0">Wishlist</h4>
                            <a href="{{ route('user.home') }}" class="btn btn-outline-dark rounded-pill btn-sm">Browse products</a>
                        </div>
                        @if (($wishlistItems ?? collect())->isNotEmpty())
                            <div class="sf-wishlist-grid">
                                @foreach ($wishlistItems as $wishlistItem)
                                    @php($wishlistProduct = $wishlistItem->product)
                                    @continue(! $wishlistProduct)
                                    @php($wishlistImage = $wishlistProduct->images->first())
                                    <div class="sf-wishlist-card">
                                        <a href="{{ route('storefront.product', $wishlistProduct) }}" class="sf-wishlist-image" aria-label="View {{ $wishlistProduct->product_name }}">
                                            <img src="{{ $wishlistImage ? asset($wishlistImage->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $wishlistProduct->product_name }}">
                                        </a>
                                        <div class="sf-wishlist-copy">
                                            <a href="{{ route('storefront.product', $wishlistProduct) }}" class="sf-wishlist-title">{{ $wishlistProduct->product_name }}</a>
                                            <div class="small text-secondary">{{ $wishlistProduct->category?->category_name ?? 'Product' }}</div>
                                            <div class="fw-semibold">{{ \App\Support\StoreCurrency::format((float) ($wishlistProduct->final_price ?: $wishlistProduct->price), 0) }}</div>
                                        </div>
                                        <form method="POST" action="{{ route('storefront.wishlist.destroy', $wishlistProduct) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">Remove</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="sf-empty-state">No wishlist products yet.</div>
                        @endif
                    </div>

                    <div class="sf-info-card mb-4">
                        <h4 class="mb-3">Recent orders</h4>
                        <div class="d-flex justify-content-end mb-3">
                            <a href="{{ route('storefront.orders.index') }}" class="btn btn-outline-dark rounded-pill btn-sm">View all orders</a>
                        </div>
                        <div class="d-grid gap-3">
                            @forelse ($orders as $order)
                                @php($latestPayment = $order->payments->last())
                                @php($orderStatus = mb_strtolower((string) $order->order_status))
                                @php($displayPaymentStatus = $orderStatus === 'cancelled' ? 'cancelled' : ($latestPayment?->status ?? $order->payment_status))
                                @php($firstItem = $order->items->first())
                                @php($firstProduct = $firstItem?->product)
                                @php($productImage = $firstProduct?->images->first())
                                @php($productUrl = $firstProduct ? route('storefront.product', $firstProduct) : null)
                                @php($firstBaseUnit = $firstItem ? \App\Support\StoreOfferPricing::orderItemBaseUnit($firstItem) : 0)
                                @php($firstOfferUnit = $firstItem ? \App\Support\StoreOfferPricing::orderItemOfferUnit($firstItem) : 0)
                                @php($firstSavings = $firstItem ? \App\Support\StoreOfferPricing::orderItemSavings($firstItem) : 0)
                                <div class="sf-sidepanel sf-recent-order p-3">
                                    <div class="sf-recent-order-row">
                                        <div class="sf-recent-order-product">
                                            @if ($productUrl)
                                                <a href="{{ $productUrl }}" class="sf-recent-order-image" aria-label="View {{ $firstItem?->item_name }}">
                                                    <img src="{{ $productImage ? asset($productImage->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $firstItem?->item_name ?? $order->order_number }}">
                                                </a>
                                            @else
                                                <div class="sf-recent-order-image" aria-hidden="true">
                                                    <img src="{{ asset('admin-theme/assets/images/product-1.png') }}" alt="">
                                                </div>
                                            @endif
                                            <div class="sf-recent-order-copy">
                                            <div class="fw-semibold">{{ $order->order_number }}</div>
                                            @if ($firstItem)
                                                @if ($productUrl)
                                                    <a href="{{ $productUrl }}" class="sf-recent-order-title">{{ $firstItem->item_name }}</a>
                                                @else
                                                    <div class="sf-recent-order-title">{{ $firstItem->item_name ?: 'Product unavailable' }}</div>
                                                    <div class="small text-secondary">Product unavailable</div>
                                                @endif
                                            @else
                                                <div class="sf-recent-order-title">Order items unavailable</div>
                                            @endif
                                            <div class="small text-secondary">By: {{ $order->vendor?->vendor_name ?? 'Store order' }}</div>
                                            <div class="sf-recent-order-details">
                                                <span>Qty: {{ $firstItem?->quantity ?? 0 }}</span>
                                                @if ($firstItem)
                                                    <span>Offer price: {{ \App\Support\StoreCurrency::format($firstOfferUnit, 0) }}</span>
                                                @endif
                                            </div>
                                            @if ($firstSavings > 0)
                                                <div class="small text-success">
                                                    Saved {{ \App\Support\StoreCurrency::format($firstSavings, 0) }}
                                                    @if ($firstBaseUnit > $firstOfferUnit)
                                                        on {{ \App\Support\StoreCurrency::format($firstBaseUnit, 0) }}
                                                    @endif
                                                </div>
                                            @endif
                                            </div>
                                        </div>

                                <div class="sf-recent-order-status">
                                    <div class="small text-secondary">Status</div>
                                    <strong>{{ ucfirst($order->order_status) }}</strong>
                                    <span class="badge rounded-pill text-bg-{{ $displayPaymentStatus === 'paid' ? 'success' : ($displayPaymentStatus === 'cancelled' ? 'secondary' : 'warning') }}">
                                        {{ ucfirst($displayPaymentStatus) }}
                                    </span>
                                </div>

                                <div class="sf-recent-order-total">
                                    <div class="small text-secondary">Total Amount</div>
                                    <strong>{{ \App\Support\StoreCurrency::format($order->total_amount, 0) }}</strong>
                                    <div class="small text-secondary">Placed {{ \App\Support\StoreDate::date($order->placed_at) }}</div>
                                    <div>
                                        <a href="{{ route('storefront.orders.show', $order) }}" class="btn btn-sm btn-outline-dark rounded-pill">View</a>
                                    </div>
                                </div>
                                    </div>
                                </div>
                            @empty
                                <x-empty-state>{{ config('ui_messages.no_orders') }}</x-empty-state>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
