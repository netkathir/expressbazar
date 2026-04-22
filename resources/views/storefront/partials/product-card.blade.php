@php($image = $product->images->first())
@php($cartEntry = $cartMap[$product->id] ?? null)
<article class="sf-product-card">
    <div class="sf-product-media">
        <a href="{{ route('storefront.product', $product) }}" class="sf-product-image">
            <img src="{{ $image ? asset($image->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $product->product_name }}">
        </a>
        @if ($cartEntry)
            <div class="sf-stepper sf-stepper-ghost">
                <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="-1" data-product="{{ $product->id }}">−</button>
                <span class="sf-stepper-value">{{ $cartEntry['quantity'] }}</span>
                <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="1" data-product="{{ $product->id }}">+</button>
            </div>
        @else
            <form method="POST" action="{{ route('storefront.cart.add', $product) }}" class="js-add-to-cart sf-card-add">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3">ADD</button>
            </form>
        @endif
    </div>
    <div class="sf-product-body">
        <a href="{{ route('storefront.product', $product) }}" class="sf-product-name">{{ $product->product_name }}</a>
        <div class="sf-product-meta">{{ $product->inventory?->unit ? $product->inventory->unit : '1 pc' }}</div>
        <div class="sf-product-price-row">
            <div>
                <div class="sf-price">₹{{ number_format((float) ($product->final_price ?: $product->price), 0) }}</div>
                @if ($product->final_price < $product->price)
                    <div class="sf-mrp">₹{{ number_format((float) $product->price, 0) }}</div>
                @endif
            </div>
        </div>
    </div>
</article>
