@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="category-hero">
            <div>
                <span class="eyebrow">Subcategory</span>
                <h1>{{ $subcategory['name'] }}</h1>
                <p>{{ $subcategory['description'] }}</p>
            </div>
            <div class="category-hero-card">
                <strong>{{ count($products ?? []) }}</strong>
                <span>products available</span>
            </div>
        </div>
    </section>

    <section class="container section-block">
        <div class="product-grid">
            @forelse ($products as $item)
                <article class="product-card product-card-grid">
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
                    <div class="pack-text">{{ $item['subcategory_name'] ?? $subcategory['name'] }}</div>
                    <div class="rating-row">★ 4.8</div>
                </article>
            @empty
                <div class="empty-state">
                    <h2>No products found</h2>
                    <p>This subcategory does not have products in the selected location yet.</p>
                </div>
            @endforelse
        </div>
    </section>
@endsection
