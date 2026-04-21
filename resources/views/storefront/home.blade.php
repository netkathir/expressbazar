@extends('layouts.storefront')

@section('content')
    <section class="hero-section">
        <div class="container hero-grid">
            <div class="hero-main">
                <p class="eyebrow">Quick commerce for everyday needs</p>
                <h1>Fast, friendly grocery shopping with a Zepto-style user experience.</h1>
                <p class="lead">ExpressBazar is designed as a clean quick-commerce storefront with category rails, product cards, promo banners, and a fast checkout flow.</p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="{{ route('category.show', 'atta-rice-dals') }}">Shop groceries</a>
                    <a class="btn btn-ghost" href="{{ route('checkout.show') }}">Go to checkout</a>
                </div>
                <div class="hero-metrics">
                    <div><strong>10 min</strong><span>delivery promise</span></div>
                    <div><strong>4.8/5</strong><span>customer rating</span></div>
                    <div><strong>500+</strong><span>daily staples</span></div>
                </div>
            </div>
            <div class="hero-side">
                <div class="promo-card primary">
                    <span class="promo-tag">Express picks</span>
                    <h3>Everyday lowest prices</h3>
                    <p>Make repeat buying obvious with focused offer cards and clean deal badges.</p>
                </div>
                <div class="promo-card secondary">
                    <span class="promo-tag">Fresh</span>
                    <h3>Fruits, dairy, pantry</h3>
                    <p>Highlight fast-moving categories with soft gradients and product-first layouts.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="container section-block">
        <div class="section-heading">
            <h2>Popular categories</h2>
            <a href="{{ route('category.show', 'fruits-vegetables') }}">See all</a>
        </div>
        <div class="category-grid">
                @foreach ($categoryCards as $category)
                    <a class="category-card" href="{{ route('category.show', $category['slug']) }}">
                    <span class="category-icon" style="background: linear-gradient(135deg, {{ $category['color'] }}, rgba(255, 255, 255, 0.92));"></span>
                    <strong>{{ $category['name'] }}</strong>
                    <small>{{ $category['description'] }}</small>
                </a>
                @endforeach
        </div>
    </section>

    <section class="container section-block split-grid">
        @foreach ($benefits as $benefit)
            <article class="info-card">
                <h3>{{ $benefit['title'] }}</h3>
                <p>{{ $benefit['text'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="container section-block">
        <div class="section-heading">
            <h2>Featured products</h2>
            <a href="{{ route('category.show', 'atta-rice-dals') }}">Browse more</a>
        </div>
        <div class="product-strip">
            @foreach ($featuredProducts as $product)
                <a class="product-card" href="{{ route('product.show', $product['slug']) }}">
                    <div class="product-image">
                        <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                        <span class="deal-badge">{{ $product['deal'] }}</span>
                        <button class="add-chip" type="button">ADD</button>
                    </div>
                    <div class="price-row">
                        <span class="price">Rs. {{ $product['price'] }}</span>
                        <span class="mrp">Rs. {{ $product['mrp'] }}</span>
                    </div>
                    <div class="save-text">Save Rs. {{ $product['mrp'] - $product['price'] }}</div>
                    <h3>{{ $product['name'] }}</h3>
                    <p>{{ $product['unit'] }}</p>
                    <div class="rating-row">* {{ $product['rating'] }}</div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="container section-block promo-grid">
        @foreach ($promoBanners as $banner)
            <article class="banner-card">
                <h3>{{ $banner['title'] }}</h3>
                <p>{{ $banner['text'] }}</p>
                <a href="{{ route('checkout.show') }}" class="btn btn-light">Order now</a>
            </article>
        @endforeach
    </section>

    <section class="container section-block">
        <div class="section-heading">
            <h2>How it works</h2>
        </div>
        <div class="steps-grid">
            @foreach ($howItWorks as $step)
                <article class="step-card">
                    <span class="step-number">{{ $loop->iteration }}</span>
                    <h3>{{ $step['title'] }}</h3>
                    <p>{{ $step['text'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="container section-block">
        <div class="section-heading">
            <h2>More to explore</h2>
            <a href="{{ route('cart.show') }}">View cart</a>
        </div>
        <div class="product-strip compact">
            @foreach ($moreProducts as $product)
                <a class="mini-card" href="{{ route('product.show', $product['slug']) }}">
                    <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
                    <div>
                        <strong>{{ $product['name'] }}</strong>
                        <p>Rs. {{ $product['price'] }} | {{ $product['unit'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endsection
