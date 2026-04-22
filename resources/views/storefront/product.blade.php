@extends('layouts.storefront')

@section('content')
    @php($cartEntry = $cartMap[$product->id] ?? null)
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">
                Home <span>›</span> {{ $product->category?->category_name ?? 'Category' }} <span>›</span> {{ $product->product_name }}
            </nav>
            <div class="sf-product-detail">
                <div class="sf-gallery">
                    <div class="sf-gallery-main">
                        <img src="{{ $product->images->first() ? asset($product->images->first()->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $product->product_name }}">
                    </div>
                    <div class="sf-gallery-thumbs">
                        @foreach ($product->images as $image)
                            <img src="{{ asset($image->image_path) }}" alt="{{ $product->product_name }}">
                        @endforeach
                    </div>
                </div>
                <div class="sf-product-summary">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <span class="badge rounded-pill text-bg-light">{{ $product->category?->category_name }}</span>
                        <span class="text-secondary small">{{ $product->vendor?->vendor_name }}</span>
                    </div>
                    <h1 class="h2 fw-bold">{{ $product->product_name }}</h1>
                    <div class="sf-rating-line mb-3">
                        <span class="sf-rating">4.3</span>
                        <span class="text-secondary small">Fast delivery ready</span>
                    </div>
                    <div class="sf-price fs-2">₹{{ number_format((float) ($product->final_price ?: $product->price), 0) }}</div>
                    <div class="text-secondary mb-3">{{ $product->inventory?->unit ?: '1 pc' }}</div>
                    <p class="lead mb-4">{{ $product->description ?: 'Fresh everyday product, ready for quick delivery.' }}</p>

                    <div class="sf-benefit-row mb-4">
                        <div class="sf-benefit">Free delivery</div>
                        <div class="sf-benefit">Best price</div>
                        <div class="sf-benefit">Quick add</div>
                    </div>

                    <form method="POST" action="{{ route('storefront.cart.add', $product) }}" class="js-add-to-cart">
                        @csrf
                        @if ($cartEntry)
                            <div class="sf-stepper">
                                <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="-1" data-product="{{ $product->id }}">−</button>
                                <span class="sf-stepper-value">{{ $cartEntry['quantity'] }}</span>
                                <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="1" data-product="{{ $product->id }}">+</button>
                            </div>
                        @else
                            <button type="submit" class="btn btn-danger btn-lg rounded-pill px-4">Add to Cart</button>
                        @endif
                    </form>
                </div>
            </div>

            <div class="sf-detail-grid mt-4">
                <div class="sf-info-card">
                    <h4 class="mb-3">Highlights</h4>
                    <dl class="sf-specs">
                        <dt>Category</dt><dd>{{ $product->category?->category_name }}</dd>
                        <dt>Subcategory</dt><dd>{{ $product->subcategory?->subcategory_name ?? '-' }}</dd>
                        <dt>Inventory</dt><dd>{{ strtoupper($product->inventory_mode ?? 'internal') }}</dd>
                        <dt>Status</dt><dd>{{ ucfirst($product->status) }}</dd>
                        <dt>Tax</dt><dd>{{ $product->tax?->tax_name ?? '-' }}</dd>
                    </dl>
                </div>
                <div class="sf-info-card">
                    <h4 class="mb-3">Coupons & offers</h4>
                    <div class="d-grid gap-2">
                        <div class="sf-coupon-row"><span>Order on Express Bazaar</span><i class="ti ti-chevron-right"></i></div>
                        <div class="sf-coupon-row"><span>Get free delivery on selected items</span><i class="ti ti-chevron-right"></i></div>
                        <div class="sf-coupon-row"><span>Quick commerce offers</span><i class="ti ti-chevron-right"></i></div>
                    </div>
                </div>
            </div>

            <div class="sf-detail-grid mt-4">
                <div class="sf-info-card">
                    <h4 class="mb-3">Related Items</h4>
                    <div class="sf-product-rail sf-product-rail-sm">
                        @foreach ($relatedProducts as $related)
                            @include('storefront.partials.product-card', ['product' => $related])
                        @endforeach
                    </div>
                </div>
                <div class="sf-info-card">
                    <h4 class="mb-3">Information</h4>
                    <dl class="sf-specs">
                        <dt>Vendor</dt><dd>{{ $product->vendor?->vendor_name ?? '-' }}</dd>
                        <dt>City</dt><dd>{{ $product->vendor?->city?->city_name ?? '-' }}</dd>
                        <dt>Zone</dt><dd>{{ $product->vendor?->zone?->zone_name ?? '-' }}</dd>
                        <dt>Price</dt><dd>₹{{ number_format((float) ($product->price), 0) }}</dd>
                    </dl>
                </div>
            </div>
        </section>
    </main>
@endsection
