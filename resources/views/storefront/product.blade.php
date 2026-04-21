@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span>/</span>
            @if (! empty($category['slug'] ?? null))
                <a href="{{ route('category.show', $category['slug']) }}">{{ $category['name'] }}</a>
                <span>/</span>
            @endif
            @if (! empty($subcategory['slug'] ?? null))
                <a href="{{ route('subcategory.show', $subcategory['slug']) }}">{{ $subcategory['name'] }}</a>
                <span>/</span>
            @endif
            <span>{{ $product['name'] }}</span>
        </div>

        <div class="product-detail-grid">
            <div class="product-detail-visual">
                <img src="{{ $product['image'] }}" alt="{{ $product['name'] }}">
            </div>

            <div class="product-detail-copy">
                <span class="eyebrow">Product detail</span>
                <h1>{{ $product['name'] }}</h1>
                <p class="summary-subtitle">{{ $product['description'] }}</p>

                <div class="highlight-card stack-card">
                    <div class="summary-row"><span>Category</span><strong>{{ $product['category_name'] ?? 'Grocery' }}</strong></div>
                    <div class="summary-row"><span>Subcategory</span><strong>{{ $product['subcategory_name'] ?? 'General' }}</strong></div>
                    <div class="summary-row"><span>SKU</span><strong>{{ $product['sku'] ?? '-' }}</strong></div>
                </div>

                @if (($vendorProducts ?? []) !== [])
                    <div class="vendor-stack">
                        @foreach ($vendorProducts as $item)
                            <div class="vendor-offer-card">
                                <div>
                                    <strong>{{ $item['vendor_name'] }}</strong>
                                    <p>Stock {{ $item['stock'] }} | Rs. {{ $item['price'] }}</p>
                                </div>

                                @if ($item['stock'] > 0)
                                    <form method="POST" action="{{ route('cart.add', $product['slug']) }}">
                                        @csrf
                                        <input type="hidden" name="vendor_product_id" value="{{ $item['id'] }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button class="btn btn-primary" type="submit">Add to cart</button>
                                    </form>
                                @else
                                    <span class="stock-pill muted">Out of stock</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    @if (($relatedProducts ?? []) !== [])
        <section class="container section-block">
            <div class="rail-head">
                <div class="section-copy">
                    <h2>Related products</h2>
                    <p>More items from the same subcategory.</p>
                </div>
            </div>

            <div class="rail-scroll compact">
                @foreach ($relatedProducts as $item)
                    <article class="product-card">
                        <div class="product-image">
                            <span class="deal-badge">{{ $item['vendor_name'] ?? 'ExpressBazar' }}</span>
                            <a href="{{ route('product.show', $item['slug']) }}">
                                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}">
                            </a>
                        </div>
                        <div class="price-row">
                            <span class="price">View</span>
                            <span class="mrp">Related</span>
                        </div>
                        <h3><a href="{{ route('product.show', $item['slug']) }}">{{ $item['name'] }}</a></h3>
                        <p>{{ $item['category_name'] ?? 'Grocery' }}</p>
                        <div class="pack-text">{{ $item['subcategory_name'] ?? '1 pack (1 kg)' }}</div>
                        <div class="rating-row">★ 4.8</div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
@endsection
