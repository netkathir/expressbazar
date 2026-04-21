@extends('layouts.storefront')

@section('content')
    @php
        $categoryTiles = collect($categories)->take(10)->values();
        $subcategoryTiles = collect($subcategories)->take(10)->values();
        $featuredSections = collect($featuredSections ?? [])->take(3)->values();
        $popularRows = $popularRows ?? [];
        $trendingRows = $trendingRows ?? [];
    @endphp

    <section id="categories" class="container section-block">
        <span class="sr-only">Groceries that feel fast, simple, and familiar.</span>
        <div class="category-rail-grid">
            @foreach ($categoryTiles as $tile)
                <a class="category-rail-card" href="{{ route('category.show', $tile['slug']) }}">
                    <img class="category-rail-thumb" src="{{ $tile['image'] }}" alt="{{ $tile['name'] }}">
                    <span>{{ $tile['name'] }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section id="subcategories" class="container section-block">
        <div class="subcategory-rail">
            @foreach ($subcategoryTiles as $tile)
                <a class="subcategory-chip" href="{{ route('subcategory.show', $tile['slug']) }}">
                    <strong>{{ $tile['name'] }}</strong>
                    <span>{{ $tile['category_name'] }}</span>
                    <small>{{ $tile['products_count'] }} products</small>
                </a>
            @endforeach
        </div>
    </section>

    <section class="container section-block">
        <div class="grid-2">
            <article class="promo-card purple promo-small promo-left">
                <div class="promo-banner-title">ALL NEW ZEPTO EXPERIENCE</div>
                <div class="banner-grid">
                    <div class="banner-mini">0 FEES</div>
                    <div class="banner-mini">EVERYDAY LOWEST PRICES</div>
                </div>
            </article>

            <article class="promo-card dark promo-small promo-right">
                <div class="promo-banner-title light">Paan Corner</div>
                <p>Get smoking accessories, fresheners and more in minutes.</p>
                <div class="hero-actions">
                    <a class="btn btn-white" href="#featured-rails">Order now</a>
                </div>
            </article>
        </div>
    </section>

    <section id="featured-rails" class="container section-block">
        @foreach ($featuredSections as $section)
            <div class="rail-section spaced">
                <div class="rail-head">
                    <div class="section-copy">
                        <h2>{{ $section['subcategory']['name'] }}</h2>
                        <p>{{ $section['subcategory']['category_name'] }}</p>
                    </div>
                    <a href="{{ route('subcategory.show', $section['subcategory']['slug']) }}">See All</a>
                </div>

                <div class="rail-scroll compact">
                    @foreach ($section['products'] as $item)
                        <article class="product-card">
                            <div class="product-image">
                                <span class="deal-badge">{{ $item['vendor_name'] }}</span>
                                <a href="{{ route('product.show', $item['product_slug']) }}">
                                    <img src="{{ $item['image'] }}" alt="{{ $item['product_name'] }}">
                                </a>
                                <form method="POST" action="{{ route('cart.add', $item['product_slug']) }}">
                                    @csrf
                                    <input type="hidden" name="vendor_product_id" value="{{ $item['id'] }}">
                                    <input type="hidden" name="quantity" value="1">
                                    <button class="product-add" type="submit">ADD</button>
                                </form>
                            </div>
                            <div class="price-row">
                                <span class="price">Rs. {{ $item['price'] }}</span>
                                <span class="mrp">Rs. {{ $item['price'] + 20 }}</span>
                            </div>
                            <div class="save-text">Rs. {{ max(1, $item['price'] - 5) }} OFF</div>
                            <h3><a href="{{ route('product.show', $item['product_slug']) }}">{{ $item['product_name'] }}</a></h3>
                            <p>{{ $item['vendor_name'] }}</p>
                            <div class="pack-text">{{ $item['subcategory_name'] ?? '1 pack (1 kg)' }}</div>
                            <div class="rating-row">★ 4.8</div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>

    <section id="trending-searches" class="container section-block">
        <div class="rail-head">
            <div class="section-copy">
                <h2>Trending Searches</h2>
            </div>
        </div>

        <div class="search-stack">
            @foreach ($trendingRows as $label => $values)
                <div class="search-row">
                    <strong>{{ $label }}</strong>
                    <p>{{ implode(' | ', $values) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section id="popular-searches" class="container section-block">
        <div class="rail-head">
            <div class="section-copy">
                <h2>Popular Searches</h2>
            </div>
        </div>

        <div class="search-stack">
            @foreach ($popularRows as $label => $values)
                <div class="search-row">
                    <strong>{{ $label }}</strong>
                    <p>{{ implode(' | ', $values) }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="container section-block">
        <div class="rail-head">
            <div class="section-copy">
                <h2>Categories</h2>
            </div>
        </div>

        <div class="category-grid wide">
            @foreach ($categoryTiles as $tile)
                <a class="category-card" href="{{ route('category.show', $tile['slug']) }}">
                    <img class="category-thumb" src="{{ $tile['image'] }}" alt="{{ $tile['name'] }}">
                    <strong>{{ $tile['name'] }}</strong>
                    <small>{{ $tile['subcategories_count'] }} subcategories</small>
                </a>
            @endforeach
        </div>
    </section>
@endsection
