@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 pt-3">
            <div class="sf-chip-row">
                @foreach ($categories->take(10) as $category)
                    <a href="{{ route('storefront.category', $category) }}" class="sf-chip">
                        <img src="{{ $category->image_path ? asset($category->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $category->category_name }}">
                        <span>{{ $category->category_name }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="container-fluid px-3 px-lg-4 mt-3">
            <div class="sf-hero-grid">
                <div class="sf-hero-card sf-hero-card-soft">
                    <span class="sf-kicker">All new Express Bazaar experience</span>
                    <h1>Quick grocery shopping with instant add-to-cart flow.</h1>
                    <p>Browse by city now, then lock the exact delivery location only when you need checkout validation.</p>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button class="btn btn-dark rounded-pill px-4 js-open-location" type="button">Set location</button>
                        <a href="#featured-sections" class="btn btn-outline-dark rounded-pill px-4">Shop now</a>
                    </div>
                </div>
                <div class="sf-hero-card sf-hero-card-dark">
                    <span class="sf-kicker text-white-50">Fast delivery</span>
                    <h2>Fresh essentials, daily deals and trending products.</h2>
                    <p>Product discovery stays open, while the cart stays locked to one vendor at a time.</p>
                    <div class="sf-hero-pills">
                        <span>City level browsing</span>
                        <span>Zone level checkout</span>
                        <span>One vendor cart</span>
                    </div>
                </div>
            </div>
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

        <section class="container-fluid px-3 px-lg-4 mt-4">
            <div class="sf-section-header">
                <div>
                    <h3>Trending near you</h3>
                    <p class="text-secondary mb-0">{{ $locationLabel === 'Select Location' ? 'Browse popular products' : 'Showing results near '.$locationLabel }}</p>
                </div>
            </div>
            <div class="sf-product-rail">
                @foreach ($featuredSections as $section)
                    @foreach ($section['products']->take(8) as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @endforeach
                @endforeach
            </div>
        </section>

        <section id="featured-sections" class="container-fluid px-3 px-lg-4 mt-5">
            @foreach ($featuredSections as $section)
                <div class="sf-section-header">
                    <div>
                        <h3>{{ $section['title'] }}</h3>
                        <p class="text-secondary mb-0">{{ $locationLabel === 'Select Location' ? 'City level discovery' : 'Deliverable to your area' }}</p>
                    </div>
                    @if ($section['subcategory'])
                        <a href="{{ route('storefront.subcategory', $section['subcategory']) }}">See all</a>
                    @endif
                </div>
                <div class="sf-product-rail mb-4">
                    @foreach ($section['products'] as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            @endforeach
        </section>

        <section class="container-fluid px-3 px-lg-4 mt-5">
            <div class="sf-info-card">
                <h4 class="mb-3">How it works</h4>
                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <div class="sf-mini-step">
                            <strong>1</strong>
                            <div>
                                <div class="fw-semibold">Open the app</div>
                                <div class="text-secondary small">Browse instantly without a blocking popup.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="sf-mini-step">
                            <strong>2</strong>
                            <div>
                                <div class="fw-semibold">Add items</div>
                                <div class="text-secondary small">Quick add keeps the cart locked to one vendor.</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="sf-mini-step">
                            <strong>3</strong>
                            <div>
                                <div class="fw-semibold">Checkout</div>
                                <div class="text-secondary small">Exact delivery validation happens only when needed.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="container-fluid px-3 px-lg-4 mt-5 mb-5">
            <div class="sf-info-card">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                    <div>
                        <h4 class="mb-1">Categories</h4>
                        <p class="text-secondary mb-0">A Zepto-style quick commerce landing page for category-first shopping.</p>
                    </div>
                </div>
                <div class="sf-category-cloud">
                    @foreach ($categories as $category)
                        <a href="{{ route('storefront.category', $category) }}">{{ $category->category_name }}</a>
                    @endforeach
                </div>
            </div>
        </section>
    </main>
@endsection
