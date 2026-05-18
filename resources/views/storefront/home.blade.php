@extends('layouts.storefront')

@section('content')
    <main class="sf-page sf-home-page">
        @php($filterQuery = array_filter([
            'pincode' => $pincode ?? null,
            'vendor_id' => $selectedVendorId ?? request('vendor_id'),
        ], fn ($value) => filled($value)))
        @php($search = $search ?? '')
        @php($isSearch = filled($search))
        @php($selectedVendorProducts = $selectedVendorProducts ?? collect())
        @php($showNoPincodeData = !empty($pincode ?? null) && !($hasPincodeProducts ?? true))
        @php($availableVendors = $vendors ?? collect())
        @php($topStatusMessage = null)
        @php($safeRouteUrl = $safeRouteUrl ?? function (string $name, string $fallback, array $parameters = [], bool $absolute = true) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                return route($name, $parameters, $absolute);
            }

            return $absolute ? url($fallback) : (parse_url(url($fallback), PHP_URL_PATH) ?: $fallback);
        })
        @php($filterQueryString = http_build_query($filterQuery))
        @php($homeFilterUrl = '/'.($filterQueryString ? '?'.$filterQueryString : ''))
        @if (!$isSearch && !empty($location ?? null) && $availableVendors->isEmpty())
            @php($topStatusMessage = config('ui_messages.no_vendors'))
        @elseif (!$isSearch && ($selectedVendor ?? null) && $selectedVendorProducts->isEmpty())
            @php($topStatusMessage = config('ui_messages.no_vendor_products'))
        @elseif (!$isSearch && $showNoPincodeData)
            @php($topStatusMessage = config('ui_messages.no_products'))
        @endif
        @if ($isSearch)
            <section class="container-fluid px-3 px-lg-4 pt-0 mb-4">
                <div class="sf-section-header">
                    <div>
                        <h3>Search Results</h3>
                        <p class="text-secondary mb-0">Results for "{{ $search }}"</p>
                    </div>
                    <a href="{{ $safeRouteUrl('user.home', $homeFilterUrl, $filterQuery) }}" class="btn btn-sm btn-light ms-2">Clear</a>
                </div>
                <div class="sf-grid js-product-list" id="product-list">
                    @include('storefront.partials.product-grid', [
                        'products' => $searchResults,
                        'emptyMessage' => 'No results found',
                    ])
                </div>
            </section>
        @endif

        <section class="container-fluid px-3 px-lg-4 pt-0 sf-category-showcase">
            <div class="sf-category-strip-header">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <h2>Shop by categories</h2>
                        <div class="sf-top-status js-storefront-status {{ $topStatusMessage ? '' : 'd-none' }}">
                            {{ $topStatusMessage }}
                        </div>
                    </div>
                    <p>Find everything you need, all in one place.</p>
                </div>
                <a href="#all-categories">See all categories <i class="ti ti-chevron-right"></i></a>
            </div>
            <div class="sf-rail-wrap sf-category-rail-wrap">
                <button type="button" class="sf-rail-arrow sf-rail-arrow-left js-rail-scroll" data-direction="-1" aria-label="Scroll categories left">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <div class="sf-chip-row">
                    @foreach ($categories as $category)
                        <a href="{{ $safeRouteUrl('storefront.category', '/categories/'.$category->getRouteKey().($filterQueryString ? '?'.$filterQueryString : ''), array_merge(['category' => $category], $filterQuery)) }}" class="sf-chip">
                            <span class="sf-chip-image">
                                <img src="{{ $category->image_path ? asset($category->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $category->category_name }}">
                            </span>
                            <span>{{ $category->category_name }}</span>
                        </a>
                    @endforeach
                </div>
                <button type="button" class="sf-rail-arrow sf-rail-arrow-right js-rail-scroll" data-direction="1" aria-label="Scroll categories right">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>
        </section>

        @if (!$isSearch)
            <section class="container-fluid px-3 px-lg-4 pt-0 sf-promo-showcase">
                <div class="sf-promo-grid">
                    <div class="sf-promo-card sf-promo-card-primary js-promo-slider" aria-label="Featured offers">
                        <div class="sf-promo-slides">
                            <div class="sf-promo-slide is-active" data-promo-slide>
                                <div class="sf-promo-copy">
                                    <span class="sf-promo-eyebrow">Daily Essentials</span>
                                    <h1>Your Daily<br>Essentials,<br><span>Delivered Fresh</span></h1>
                                    <p>Shop groceries, fruits, spices, and more with instant add-to-cart and fast delivery.</p>
                                    <div class="sf-promo-actions">
                                        <a href="#featured-sections" class="btn btn-dark rounded-pill px-4">Shop Now <i class="ti ti-arrow-right ms-1"></i></a>
                                        <button class="btn btn-outline-dark rounded-pill px-4 js-open-location" type="button">Set Location <i class="ti ti-map-pin ms-1"></i></button>
                                    </div>
                                </div>
                                <div class="sf-promo-visual" aria-hidden="true">
                                    <div class="sf-promo-offer-frame">
                                        <img src="{{ asset('sample-assets/banner/1560931223Oq6Ox.jpg') }}" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="sf-promo-slide" data-promo-slide>
                                <div class="sf-promo-copy">
                                    <span class="sf-promo-eyebrow">Fresh Deals</span>
                                    <h1>Fruits &amp;<br>Vegetables,<br><span>Big Savings</span></h1>
                                    <p>Pick fresh produce for daily cooking with quick browsing and one-vendor checkout.</p>
                                    <div class="sf-promo-actions">
                                        <a href="#top-offers" class="btn btn-dark rounded-pill px-4">View Offers <i class="ti ti-arrow-right ms-1"></i></a>
                                        <button class="btn btn-outline-dark rounded-pill px-4 js-open-location" type="button">Set Location <i class="ti ti-map-pin ms-1"></i></button>
                                    </div>
                                </div>
                                <div class="sf-promo-visual" aria-hidden="true">
                                    <div class="sf-promo-offer-frame">
                                        <img src="{{ asset('sample-assets/banner/1562925151VT7ep.jpg') }}" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="sf-promo-slide" data-promo-slide>
                                <div class="sf-promo-copy">
                                    <span class="sf-promo-eyebrow">Weekend Offer</span>
                                    <h1>Stock Up<br>Pantry Picks,<br><span>Save More</span></h1>
                                    <p>Find cooking essentials, snacks, and household favorites from nearby vendors.</p>
                                    <div class="sf-promo-actions">
                                        <a href="#featured-sections" class="btn btn-dark rounded-pill px-4">Shop Deals <i class="ti ti-arrow-right ms-1"></i></a>
                                        <button class="btn btn-outline-dark rounded-pill px-4 js-open-location" type="button">Set Location <i class="ti ti-map-pin ms-1"></i></button>
                                    </div>
                                </div>
                                <div class="sf-promo-visual" aria-hidden="true">
                                    <div class="sf-promo-offer-frame">
                                        <img src="{{ asset('sample-assets/banner/1562925306rlzPX.jpg') }}" alt="">
                                    </div>
                                </div>
                            </div>
                            <div class="sf-promo-slide" data-promo-slide>
                                <div class="sf-promo-copy">
                                    <span class="sf-promo-eyebrow">Limited Discount</span>
                                    <h1>Hot Offers<br>Near You,<br><span>Ready Fast</span></h1>
                                    <p>Browse local offers by city, select your vendor, and keep checkout simple.</p>
                                    <div class="sf-promo-actions">
                                        <a href="#top-offers" class="btn btn-dark rounded-pill px-4">Explore Deals <i class="ti ti-arrow-right ms-1"></i></a>
                                        <button class="btn btn-outline-dark rounded-pill px-4 js-open-location" type="button">Set Location <i class="ti ti-map-pin ms-1"></i></button>
                                    </div>
                                </div>
                                <div class="sf-promo-visual" aria-hidden="true">
                                    <div class="sf-promo-offer-frame">
                                        <img src="{{ asset('sample-assets/banner/1563889380AxUAu.jpg') }}" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="sf-promo-dots" role="tablist" aria-label="Choose featured offer">
                            <button class="is-active" type="button" data-promo-dot aria-label="Show daily essentials offer"></button>
                            <button type="button" data-promo-dot aria-label="Show fresh deals offer"></button>
                            <button type="button" data-promo-dot aria-label="Show pantry offer"></button>
                            <button type="button" data-promo-dot aria-label="Show local discount offer"></button>
                        </div>
                    </div>

                    <div class="sf-promo-card sf-promo-card-deal">
                        <div class="sf-promo-copy">
                            <span class="sf-promo-kicker">Best Deals For You</span>
                            <h2>Fresh essentials,<br>big savings!</h2>
                            <div class="sf-promo-benefits">
                                <span><i class="ti ti-map-pin"></i>City Level<br>Browsing</span>
                                <span><i class="ti ti-map-check"></i>Zone Level<br>Checkout</span>
                                <span><i class="ti ti-basket"></i>One Vendor<br>Cart</span>
                            </div>
                            <a href="#top-offers" class="btn btn-warning rounded-pill px-4">Explore Deals <i class="ti ti-arrow-right ms-1"></i></a>
                        </div>
                        <div class="sf-deal-basket" aria-hidden="true">
                            <div class="sf-deal-product-card">
                                <img src="{{ asset('sample-assets/item/1560860289I4Gx1.jpg') }}" alt="">
                            </div>
                            <strong>UP TO<br>30%<br>OFF</strong>
                        </div>
                    </div>
                </div>

                <div class="sf-service-strip">
                    <div><i class="ti ti-leaf"></i><strong>Fresh &amp; Quality</strong><span>100% quality assured</span></div>
                    <div><i class="ti ti-category"></i><strong>Wide Range</strong><span>1000+ products</span></div>
                    <div><i class="ti ti-truck-delivery"></i><strong>Fast Delivery</strong><span>Quick &amp; reliable</span></div>
                    <div><i class="ti ti-shield-check"></i><strong>Secure Payments</strong><span>100% secure checkout</span></div>
                    <div><i class="ti ti-refresh"></i><strong>Easy Returns</strong><span>Hassle-free returns</span></div>
                </div>
            </section>
        @endif

        @if (!$isSearch && ($selectedVendor ?? null))
            <section class="container-fluid px-3 px-lg-4 mt-4">
                <div class="sf-section-header">
                    <div>
                        <h3>{{ $selectedVendor->vendor_name }}</h3>
                        <p class="text-secondary mb-0">Products from your selected vendor</p>
                    </div>
                </div>
                <div class="sf-rail-wrap">
                    <button type="button" class="sf-rail-arrow sf-rail-arrow-left js-rail-scroll" data-direction="-1" aria-label="Scroll vendor products left">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="sf-product-rail">
                        @forelse ($selectedVendorProducts as $product)
                            @include('storefront.partials.product-card', ['product' => $product])
                        @empty
                            <x-empty-state>No products available</x-empty-state>
                        @endforelse
                    </div>
                    <button type="button" class="sf-rail-arrow sf-rail-arrow-right js-rail-scroll" data-direction="1" aria-label="Scroll vendor products right">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
            </section>
        @endif

        @if (!$isSearch && $showNoPincodeData)
            <section class="container-fluid px-3 px-lg-4 mt-4">
                <x-empty-state>{{ config('ui_messages.no_products') }}</x-empty-state>
            </section>
        @elseif (!$isSearch)
            @if (($discountedProducts ?? collect())->isNotEmpty())
                @php($topOfferCategory = $discountedProducts->first()?->category ?? $categories->first())
                <section id="top-offers" class="container-fluid px-3 px-lg-4 mt-4">
                    <div class="sf-section-header">
                        <div>
                            <h3>Top offers today</h3>
                            <p class="text-secondary mb-0">Discounted products picked from the live sample catalog.</p>
                        </div>
                        @if ($topOfferCategory)
                            <a href="{{ $safeRouteUrl('storefront.category', '/categories/'.$topOfferCategory->getRouteKey().($filterQueryString ? '?'.$filterQueryString : ''), array_merge(['category' => $topOfferCategory], $filterQuery)) }}">See all</a>
                        @endif
                    </div>
                    <div class="sf-rail-wrap">
                        <button type="button" class="sf-rail-arrow sf-rail-arrow-left js-rail-scroll" data-direction="-1" aria-label="Scroll offers left">
                            <i class="ti ti-chevron-left"></i>
                        </button>
                        <div class="sf-product-rail">
                            @foreach ($discountedProducts as $product)
                                @include('storefront.partials.product-card', ['product' => $product])
                            @endforeach
                        </div>
                        <button type="button" class="sf-rail-arrow sf-rail-arrow-right js-rail-scroll" data-direction="1" aria-label="Scroll offers right">
                            <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>
                </section>
            @endif

            <section class="container-fluid px-3 px-lg-4 mt-4">
                <div class="sf-section-header">
                    <div>
                        <h3>Trending near you</h3>
                        <p class="text-secondary mb-0">{{ $locationLabel === 'Select Location' ? 'Browse popular products' : 'Showing results near '.$locationLabel }}</p>
                    </div>
                    <a href="#featured-sections">See all</a>
                </div>
                <div class="sf-rail-wrap">
                    <button type="button" class="sf-rail-arrow sf-rail-arrow-left js-rail-scroll" data-direction="-1" aria-label="Scroll trending products left">
                        <i class="ti ti-chevron-left"></i>
                    </button>
                    <div class="sf-product-rail">
                        @foreach ($featuredSections as $section)
                            @foreach ($section['products']->take(8) as $product)
                                @include('storefront.partials.product-card', ['product' => $product])
                            @endforeach
                        @endforeach
                    </div>
                    <button type="button" class="sf-rail-arrow sf-rail-arrow-right js-rail-scroll" data-direction="1" aria-label="Scroll trending products right">
                        <i class="ti ti-chevron-right"></i>
                    </button>
                </div>
            </section>

            <section id="featured-sections" class="container-fluid px-3 px-lg-4 mt-5">
                @foreach ($featuredSections as $section)
                    <div class="sf-section-header">
                        <div>
                            <h3>{{ $section['title'] }}</h3>
                            <p class="text-secondary mb-0">{{ $locationLabel === 'Select Location' ? 'City level discovery' : 'Deliverable to your area' }}</p>
                        </div>
                        @if (!empty($section['subcategory'] ?? null))
                            <a href="{{ $safeRouteUrl('storefront.subcategory', '/subcategories/'.$section['subcategory']->getRouteKey().($filterQueryString ? '?'.$filterQueryString : ''), array_merge(['subcategory' => $section['subcategory']], $filterQuery)) }}">See all</a>
                        @endif
                    </div>
                    <div class="sf-rail-wrap mb-4">
                        <button type="button" class="sf-rail-arrow sf-rail-arrow-left js-rail-scroll" data-direction="-1" aria-label="Scroll products left">
                            <i class="ti ti-chevron-left"></i>
                        </button>
                        <div class="sf-product-rail">
                            @foreach ($section['products'] as $product)
                                @include('storefront.partials.product-card', ['product' => $product])
                            @endforeach
                        </div>
                        <button type="button" class="sf-rail-arrow sf-rail-arrow-right js-rail-scroll" data-direction="1" aria-label="Scroll products right">
                            <i class="ti ti-chevron-right"></i>
                        </button>
                    </div>
                @endforeach
            </section>

            @if (($banners ?? collect())->isNotEmpty())
                <section class="container-fluid px-3 px-lg-4 mt-4">
                    <div class="sf-banner-grid">
                        @foreach ($banners as $banner)
                            <a href="{{ $banner->link_url ?: '#' }}" class="sf-banner-card" style="background-image: linear-gradient(135deg, rgba(0,0,0,.25), rgba(0,0,0,.1)), url('{{ asset($banner->image_path) }}');">
                                <div class="sf-banner-copy">
                                    <span class="sf-kicker text-white-50">Featured</span>
                                    <h2>{{ $banner->title }}</h2>
                                    @if ($banner->subtitle)
                                        <p>{{ $banner->subtitle }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endif

        <section class="container-fluid px-3 px-lg-4 mt-5">
            <div class="sf-info-card sf-how-it-works">
                <div class="sf-how-header">
                    <div>
                        <span class="sf-kicker">Simple flow</span>
                        <h4 class="mb-0">How it works</h4>
                    </div>
                    <span class="sf-how-badge">3 steps</span>
                </div>
                <div class="row g-3 sf-how-grid">
                    <div class="col-12 col-md-4">
                        <div class="sf-mini-step">
                            <strong>1</strong>
                            <i class="ti ti-compass"></i>
                            <span class="sf-step-line"></span>
                            <div>
                                <div class="fw-semibold">Open the app</div>
                                <div class="text-secondary small">Browse instantly without a blocking popup.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="sf-mini-step">
                            <strong>2</strong>
                            <i class="ti ti-basket-plus"></i>
                            <span class="sf-step-line"></span>
                            <div>
                                <div class="fw-semibold">Add items</div>
                                <div class="text-secondary small">Quick add keeps the cart locked to one vendor.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="sf-mini-step">
                            <strong>3</strong>
                            <i class="ti ti-map-check"></i>
                            <span class="sf-step-line"></span>
                            <div>
                                <div class="fw-semibold">Checkout</div>
                                <div class="text-secondary small">Exact delivery validation happens only when needed.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="all-categories" class="container-fluid px-3 px-lg-4 mt-5 mb-5">
            <div class="sf-info-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">Categories</h4>
                    </div>
                </div>
                <div class="sf-category-cloud">
                    @foreach ($categories as $category)
                        <a href="{{ $safeRouteUrl('storefront.category', '/categories/'.$category->getRouteKey().($filterQueryString ? '?'.$filterQueryString : ''), array_merge(['category' => $category], $filterQuery)) }}">{{ $category->category_name }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection
