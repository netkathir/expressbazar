@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span>/</span>
            <span>{{ $category['name'] }}</span>
        </div>

        <div class="section-heading hero-inline">
            <div>
                <h1>{{ $category['name'] }}</h1>
                <p>{{ $category['description'] }}</p>
            </div>
            <a class="btn btn-primary" href="{{ route('checkout.show') }}">Checkout</a>
        </div>
    </section>

    <section class="container section-block">
        <div class="filter-row">
            <span class="filter-chip active">All</span>
            <span class="filter-chip">Top rated</span>
            <span class="filter-chip">Under 99</span>
            <span class="filter-chip">Fast delivery</span>
        </div>

        <div class="product-grid">
            @foreach ($products as $product)
                <a class="product-card wide" href="{{ route('product.show', $product['slug']) }}">
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
@endsection
