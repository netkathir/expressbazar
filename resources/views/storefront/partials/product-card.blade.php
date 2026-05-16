@php($image = $product->images->first())
@php($basePrice = (float) $product->price)
@php($salePrice = (float) ($product->final_price ?: $product->price))
@php($hasDiscount = $basePrice > 0 && $salePrice >= 0 && $salePrice < $basePrice)
@php($discountAmount = $hasDiscount ? $basePrice - $salePrice : 0)
@php($discountLabel = $hasDiscount
    ? ($product->discount_type === 'percentage'
        ? max(1, (int) round((($basePrice - $salePrice) / $basePrice) * 100)).'% OFF'
        : 'SAVE '.\App\Support\StoreCurrency::format($discountAmount, 0))
    : null)
@php($currentPincode = request('pincode') ?: request('postcode') ?: ($pincode ?? null))
@php($pincodeQuery = array_filter([
    'pincode' => $currentPincode,
    'vendor_id' => request('vendor_id'),
], fn ($value) => filled($value)))
@php($safeRouteUrl = $safeRouteUrl ?? function (string $name, string $fallback, array $parameters = [], bool $absolute = true) {
    if (\Illuminate\Support\Facades\Route::has($name)) {
        return route($name, $parameters, $absolute);
    }

    return $absolute ? url($fallback) : (parse_url(url($fallback), PHP_URL_PATH) ?: $fallback);
})
@php($pincodeQueryString = http_build_query($pincodeQuery))
@php($productFallbackUrl = '/products/'.$product->getRouteKey().($pincodeQueryString ? '?'.$pincodeQueryString : ''))

<article class="sf-product-card">
    <div class="sf-product-media">
        <a href="{{ $safeRouteUrl('storefront.product', $productFallbackUrl, array_merge(['product' => $product], $pincodeQuery)) }}" class="sf-product-image">
            <img src="{{ $image ? asset($image->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $product->product_name }}">
        </a>

        <form method="POST" action="{{ $safeRouteUrl('storefront.cart.add', '/cart/items/'.$product->getRouteKey(), ['product' => $product]) }}" class="js-add-to-cart sf-card-add">
            @csrf
            <button type="submit" class="btn btn-sm rounded-pill px-3">ADD</button>
        </form>
    </div>

    <div class="sf-product-body">
        <a href="{{ $safeRouteUrl('storefront.product', $productFallbackUrl, array_merge(['product' => $product], $pincodeQuery)) }}" class="sf-product-name">{{ $product->product_name }}</a>
        <small class="text-secondary d-block">Sold by: {{ $product->vendor?->vendor_name ?? 'Vendor not available' }}</small>
        <div class="sf-product-meta">{{ $product->inventory?->unit ? $product->inventory->unit : '1 pc' }}</div>
        <div class="sf-product-price-row">
            <div>
                <div class="sf-product-price-line">
                    <span class="sf-price">{{ \App\Support\StoreCurrency::format($salePrice, 0) }}</span>
                    @if ($hasDiscount)
                        <span class="sf-product-saving">{{ $discountLabel }}</span>
                    @endif
                </div>
                @if ($hasDiscount)
                    <div class="small text-success">Offer price</div>
                    <div class="sf-mrp">{{ \App\Support\StoreCurrency::format($basePrice, 0) }}</div>
                    <div class="small text-success">You save {{ \App\Support\StoreCurrency::format($discountAmount, 0) }}</div>
                @endif
            </div>

        </div>
    </div>
</article>
