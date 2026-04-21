@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span>/</span>
            <span>{{ $product['name'] }}</span>
        </div>

        <div class="product-layout">
            <div class="product-visual">
                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
            </div>
            <div class="product-summary">
                <span class="deal-badge inline">{{ $product['deal'] }}</span>
                <h1>{{ $product['name'] }}</h1>
                <p class="muted">{{ $product['description'] }}</p>
                <div class="price-row large">
                    <span class="price">Rs. {{ $product['price'] }}</span>
                    <span class="mrp">Rs. {{ $product['mrp'] }}</span>
                </div>
                <div class="rating-row">* {{ $product['rating'] }} rating</div>
                <p class="muted">{{ $product['unit'] }} | Delivery in 10-20 minutes</p>
                <div class="action-row">
                    <button class="btn btn-ghost" type="button">-</button>
                    <button class="btn btn-primary" type="button">Add to cart</button>
                    <button class="btn btn-ghost" type="button">+</button>
                </div>
                <div class="feature-list">
                    <div>Fresh stock and clear pricing</div>
                    <div>Easy substitutions and repeat buys</div>
                    <div>Supports cart, checkout, and order flow</div>
                </div>
            </div>
        </div>
    </section>

    <section class="container section-block">
        <div class="section-heading">
            <h2>Related products</h2>
        </div>
        <div class="product-strip compact">
            @foreach ($relatedProducts as $related)
                <a class="mini-card" href="{{ route('product.show', $related['slug']) }}">
                    <img src="{{ $related['image'] }}" alt="{{ $related['name'] }}">
                    <div>
                        <strong>{{ $related['name'] }}</strong>
                        <p>Rs. {{ $related['price'] }} | {{ $related['unit'] }}</p>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endsection
