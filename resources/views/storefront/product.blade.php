@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        @php
            $basePrice = (float) $product->price;
            $salePrice = (float) ($product->final_price ?: $product->price);
            $hasDiscount = $basePrice > 0 && $salePrice >= 0 && $salePrice < $basePrice;
            $discountAmount = $hasDiscount ? $basePrice - $salePrice : 0;
            $discountLabel = $hasDiscount
                ? ($product->discount_type === 'percentage'
                    ? rtrim(rtrim(number_format((float) $product->discount_value, 2), '0'), '.').'% off'
                    : \App\Support\StoreCurrency::format((float) $product->discount_value, 0).' off')
                : null;
            $productImages = \App\Support\StoreImage::productGallery($product);
            $firstImage = $productImages->first();
            $stockLimit = $product->inventory?->inventory_mode === 'internal'
                ? max(1, min(99, (int) $product->inventory?->stock_quantity))
                : 99;
        @endphp
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">
                <a href="{{ route('user.home') }}">Home</a>
                <span>&rsaquo;</span>
                @if ($product->category)
                    <a href="{{ route('storefront.category', $product->category) }}">{{ $product->category->category_name }}</a>
                @else
                    Category
                @endif
                <span>&rsaquo;</span>
                {{ $product->product_name }}
            </nav>
            <div class="sf-product-detail">
                <div class="sf-gallery">
                    <div class="sf-gallery-thumbs" aria-label="Product images">
                        @foreach ($productImages as $index => $image)
                            <button
                                type="button"
                                class="sf-gallery-thumb js-gallery-thumb {{ $loop->first ? 'is-active' : '' }}"
                                data-gallery-image="{{ asset($image->image_path) }}"
                                aria-label="View image {{ $index + 1 }} of {{ $productImages->count() }}"
                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                            >
                                <img src="{{ asset($image->image_path) }}" alt="{{ $product->product_name }} thumbnail {{ $index + 1 }}" onerror="{{ \App\Support\StoreImage::onError('product') }}">
                            </button>
                        @endforeach
                    </div>
                    <div class="sf-gallery-main">
                        <button type="button" class="sf-gallery-nav sf-gallery-nav-prev js-gallery-nav" data-direction="-1" aria-label="Previous product image">
                            <i class="ti ti-chevron-left"></i>
                        </button>
                        <img class="js-gallery-main-image" src="{{ asset($firstImage->image_path) }}" alt="{{ $product->product_name }}" onerror="{{ \App\Support\StoreImage::onError('product') }}">
                        <button type="button" class="sf-gallery-nav sf-gallery-nav-next js-gallery-nav" data-direction="1" aria-label="Next product image">
                            <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div class="sf-product-summary">
                    <div class="d-flex align-items-start justify-content-between gap-3">
                        <h1 class="h2 fw-bold">{{ $product->product_name }}</h1>
                        @if (!empty($wishlistAvailable))
                            @auth
                                @if (auth()->user()->role === 'customer')
                                    <form method="POST" action="{{ $isWishlisted ? route('storefront.wishlist.destroy', $product) : route('storefront.wishlist.store', $product) }}">
                                        @csrf
                                        @if ($isWishlisted)
                                            @method('DELETE')
                                        @endif
                                        <button type="submit" class="sf-wishlist-btn {{ $isWishlisted ? 'is-active' : '' }}" aria-label="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}">
                                            <i class="ti {{ $isWishlisted ? 'ti-heart-filled' : 'ti-heart' }}"></i>
                                        </button>
                                    </form>
                                @endif
                            @else
                                <a href="{{ route('storefront.login') }}" class="sf-wishlist-btn" aria-label="Login to add to wishlist">
                                    <i class="ti ti-heart"></i>
                                </a>
                            @endauth
                        @elseif (app()->environment('local'))
                            @auth
                                @if (auth()->user()->role === 'customer')
                                    <button type="button" class="sf-wishlist-btn" disabled aria-label="Wishlist unavailable">
                                        <i class="ti ti-heart"></i>
                                    </button>
                                @endif
                            @else
                                <a href="{{ route('storefront.login') }}" class="sf-wishlist-btn" aria-label="Login to add to wishlist">
                                    <i class="ti ti-heart"></i>
                                </a>
                            @endauth
                        @endif
                    </div>
                    <div class="sf-rating-line mb-3">
                        <span class="sf-rating">4.3</span>
                        <span class="text-secondary small">Fast delivery ready</span>
                    </div>
                    <div class="d-flex flex-wrap align-items-end gap-2 mb-1">
                        <div class="sf-price fs-2">{{ \App\Support\StoreCurrency::format($salePrice, 0) }}</div>
                        @if ($hasDiscount)
                            <div class="sf-mrp fs-5 mb-1">{{ \App\Support\StoreCurrency::format($basePrice, 0) }}</div>
                        @endif
                    </div>
                    @if ($hasDiscount)
                        <div class="sf-product-offer mb-3">
                            <i class="ti ti-tag"></i>
                            <span>{{ $discountLabel }}. You save {{ \App\Support\StoreCurrency::format($discountAmount, 0) }}</span>
                        </div>
                    @endif
                    <div class="text-secondary mb-3">{{ $product->inventory?->unit ?: '1 pc' }}</div>
                    <p class="lead mb-4">{{ $product->description ?: 'Fresh everyday product, ready for quick delivery.' }}</p>

                    <div class="sf-benefit-row mb-4">
                        <div class="sf-benefit">Free delivery</div>
                        <div class="sf-benefit">Best price</div>
                        <div class="sf-benefit">Quick add</div>
                    </div>

                    <form method="POST" action="{{ route('storefront.cart.add', $product) }}" class="js-add-to-cart sf-product-actions">
                        @csrf
                        <div class="sf-product-quantity" aria-label="Choose product quantity">
                            <button type="button" class="sf-product-qty-btn js-product-qty" data-delta="-1" aria-label="Decrease quantity">
                                <i class="ti ti-minus"></i>
                            </button>
                            <input
                                type="number"
                                name="quantity"
                                class="sf-product-qty-input js-product-qty-input"
                                value="1"
                                min="1"
                                max="{{ $stockLimit }}"
                                inputmode="numeric"
                                aria-label="Quantity"
                            >
                            <button type="button" class="sf-product-qty-btn js-product-qty" data-delta="1" aria-label="Increase quantity">
                                <i class="ti ti-plus"></i>
                            </button>
                        </div>
                        <button type="submit" class="btn btn-danger btn-lg rounded-pill px-4">Add to Cart</button>
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
                        <dt>Tax</dt><dd>{{ $product->tax ? $product->tax->tax_name.' ('.rtrim(rtrim(number_format((float) $product->tax->tax_percentage, 2), '0'), '.').'%)' : '-' }}</dd>
                    </dl>
                </div>
                <div class="sf-info-card">
                    <h4 class="mb-3">Product description</h4>
                    <dl class="sf-specs">
                        <dt>Unit</dt><dd>{{ $product->inventory?->unit ?: '1 pc' }}</dd>
                        <dt>Availability</dt><dd>{{ $product->inventory?->inventory_mode === 'internal' ? ((int) $product->inventory?->stock_quantity > 0 ? 'In stock' : 'Out of stock') : 'Vendor managed' }}</dd>
                        <dt>Seller</dt><dd>{{ $product->vendor?->vendor_name ?? '-' }}</dd>
                        <dt>Offer</dt><dd>{{ $hasDiscount ? $discountLabel.' (save '.\App\Support\StoreCurrency::format($discountAmount, 0).')' : 'No active offer' }}</dd>
                    </dl>
                </div>
            </div>

            @if ($relatedProducts->isNotEmpty())
                <div class="sf-detail-grid mt-4">
                    <div class="sf-info-card">
                        <h4 class="mb-3">Related Items</h4>
                        <div class="sf-rail-wrap">
                            <button type="button" class="sf-rail-arrow sf-rail-arrow-left js-rail-scroll" data-direction="-1" aria-label="Scroll related products left">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <div class="sf-product-rail sf-product-rail-sm">
                                @foreach ($relatedProducts as $related)
                                    @include('storefront.partials.product-card', ['product' => $related])
                                @endforeach
                            </div>
                            <button type="button" class="sf-rail-arrow sf-rail-arrow-right js-rail-scroll" data-direction="1" aria-label="Scroll related products right">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="sf-info-card">
                        <h4 class="mb-3">Information</h4>
                        <dl class="sf-specs">
                            <dt>Vendor</dt><dd>{{ $product->vendor?->vendor_name ?? '-' }}</dd>
                            <dt>City</dt><dd>{{ $product->vendor?->city?->city_name ?? '-' }}</dd>
                            <dt>Zone</dt><dd>{{ $product->vendor?->zone?->zone_name ?? '-' }}</dd>
                            <dt>Price</dt><dd>{{ \App\Support\StoreCurrency::format((float) $product->price, 0) }}</dd>
                            <dt>Discount</dt><dd>{{ $hasDiscount ? \App\Support\StoreCurrency::format($discountAmount, 0) : '-' }}</dd>
                        </dl>
                    </div>
                </div>
            @endif
        </section>
    </main>
@endsection
