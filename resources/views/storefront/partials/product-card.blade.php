@php($image = $product->images->first())
@php($cartEntry = $cartMap[$product->id] ?? null)
@php($basePrice = (float) $product->price)
@php($salePrice = (float) ($product->final_price ?: $product->price))
@php($hasDiscount = $basePrice > 0 && $salePrice > 0 && $salePrice < $basePrice)
@php($discountPercent = $hasDiscount ? max(1, (int) round((($basePrice - $salePrice) / $basePrice) * 100)) : null)
@php($currentPincode = request('pincode') ?: request('postcode') ?: ($pincode ?? null))
@php($pincodeQuery = array_filter([
    'pincode' => $currentPincode,
    'vendor_id' => request('vendor_id'),
], fn ($value) => filled($value)))

<article class="sf-product-card">
    <div class="sf-product-media">
        <a href="{{ route('storefront.product', array_merge(['product' => $product], $pincodeQuery)) }}" class="sf-product-image">
            <img src="{{ $image ? asset($image->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $product->product_name }}">
        </a>

        @if ($cartEntry)
            <div class="sf-stepper sf-stepper-ghost">
                <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="-1" data-product="{{ $product->id }}">-</button>
                <span class="sf-stepper-value">{{ $cartEntry['quantity'] }}</span>
                <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="1" data-product="{{ $product->id }}">+</button>
            </div>
        @else
            <form method="POST" action="{{ route('storefront.cart.add', $product) }}" class="js-add-to-cart sf-card-add">
                @csrf
                <button type="submit" class="btn btn-sm rounded-pill px-3">ADD</button>
            </form>
        @endif
    </div>

    <div class="sf-product-body">
        <a href="{{ route('storefront.product', array_merge(['product' => $product], $pincodeQuery)) }}" class="sf-product-name">{{ $product->product_name }}</a>
        <small class="text-secondary d-block">Sold by: {{ $product->vendor?->vendor_name ?? 'Vendor not available' }}</small>
        <div class="sf-product-meta">{{ $product->inventory?->unit ? $product->inventory->unit : '1 pc' }}</div>
        <div class="sf-product-price-row">
            <div>
                <div class="sf-product-price-line">
                    <span class="sf-price">&#8377;{{ number_format($salePrice, 0) }}</span>
                    @if ($hasDiscount)
                        <span class="sf-product-saving">{{ $discountPercent }}% OFF</span>
                    @endif
                </div>
                @if ($hasDiscount)
                    <div class="sf-mrp">&#8377;{{ number_format($basePrice, 0) }}</div>
                @endif
            </div>

            @unless ($cartEntry)
                <form method="POST" action="{{ route('storefront.cart.add', $product) }}" class="js-add-to-cart sf-card-plus">
                    @csrf
                    <button type="submit" aria-label="Add {{ $product->product_name }} to cart">+</button>
                </form>
            @endunless
        </div>
    </div>
</article>
