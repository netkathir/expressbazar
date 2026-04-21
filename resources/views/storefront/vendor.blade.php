@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="breadcrumb">
            <a href="{{ route('home') }}?location_id={{ $selectedLocationId }}">Home</a>
            <span>/</span>
            <span>{{ $vendor['name'] }}</span>
        </div>

        <div class="section-title">
            <div class="section-copy">
                <h1>{{ $vendor['name'] }}</h1>
                <p>{{ $vendor['description'] ?? 'Vendor store' }}</p>
            </div>
        </div>
    </section>

    <section class="container section-block">
        @if ($products === [])
            <div class="empty-state">
                <h2>No products for this vendor</h2>
                <p>Add vendor products from the admin panel.</p>
            </div>
        @else
            <div class="product-grid">
                @foreach ($products as $item)
                    <a class="product-card compact" href="{{ route('product.show', $item['product_slug']) }}?location_id={{ $selectedLocationId }}">
                        <div class="product-image">
                            <span class="deal-badge">{{ $item['vendor_name'] }}</span>
                            <img src="{{ $item['image'] }}" alt="{{ $item['product_name'] }}">
                        </div>
                        <div class="price-row">
                            <span class="price">Rs. {{ $item['price'] }}</span>
                            <span class="muted">Stock {{ $item['stock'] }}</span>
                        </div>
                        <h3>{{ $item['product_name'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                        <div class="pack-text">{{ $item['subcategory_name'] ?? '1 pack (1 kg)' }}</div>
                        <div class="rating-row">★ 4.8</div>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
@endsection
