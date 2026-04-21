@extends('layouts.storefront')

@section('content')
    @php
        $featuredSections = collect($featuredSections ?? [])->values();
    @endphp

    <section class="container section-block">
        <div class="category-hero">
            <div>
                <span class="eyebrow">Category</span>
                <h1>{{ $category['name'] }}</h1>
                <p>{{ $category['description'] }}</p>
            </div>
            <div class="category-hero-card">
                <strong>{{ count($products ?? []) }}</strong>
                <span>products available</span>
            </div>
        </div>
    </section>

    @if (($subcategories ?? []) !== [])
        <section class="container section-block">
            <div class="subcategory-rail">
                @foreach ($subcategories as $tile)
                    <a class="subcategory-chip" href="{{ route('subcategory.show', $tile['slug']) }}">
                        <strong>{{ $tile['name'] }}</strong>
                        <span>{{ $tile['category_name'] }}</span>
                        <small>{{ $tile['products_count'] }} products</small>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="container section-block">
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
                            <div class="pack-text">{{ $item['subcategory_name'] ?? $section['subcategory']['name'] }}</div>
                            <div class="rating-row">★ 4.8</div>
                        </article>
                    @endforeach
                </div>
            </div>
        @endforeach
    </section>
@endsection
