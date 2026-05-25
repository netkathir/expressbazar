@php
    $items = $cartItems ?? collect();
    $offerSavings = \App\Support\StoreOfferPricing::cartSavings($items);
    $safeRouteUrl = $safeRouteUrl ?? function (string $name, string $fallback, array $parameters = [], bool $absolute = true) {
        if (\Illuminate\Support\Facades\Route::has($name)) {
            return route($name, $parameters, $absolute);
        }

        return $absolute ? url($fallback) : (parse_url(url($fallback), PHP_URL_PATH) ?: $fallback);
    };
@endphp
<div class="sf-cart-panel">
    <div class="sf-cart-header">
        <div>
            <div class="fw-semibold">Cart</div>
            <div class="small text-secondary">{{ $cartCount ?? 0 }} items</div>
        </div>
        <button type="button" class="btn-close js-close-cart"></button>
    </div>

    <div class="sf-cart-body">
        @forelse ($items as $item)
            @php
                $baseUnit = \App\Support\StoreOfferPricing::cartItemBaseUnit($item);
                $offerUnit = \App\Support\StoreOfferPricing::cartItemOfferUnit($item);
                $itemSavings = \App\Support\StoreOfferPricing::cartItemSavings($item);
                $discountLabel = \App\Support\StoreOfferPricing::discountLabel($item['product'], $baseUnit, $offerUnit);
            @endphp
            <div class="sf-cart-item">
                <img src="{{ \App\Support\StoreImage::product($item['product']) }}" alt="{{ $item['product']->product_name }}" onerror="{{ \App\Support\StoreImage::onError('product') }}">
                <div class="flex-grow-1">
                    <div class="fw-semibold small">{{ $item['product']->product_name }}</div>
                    <div class="small text-secondary">{{ $item['product']->vendor?->vendor_name }}</div>
                    <div class="small text-secondary">
                        Offer price: <span class="fw-semibold text-success">{{ \App\Support\StoreCurrency::format($offerUnit, 0) }}</span>
                        @if ($baseUnit > $offerUnit)
                            <span class="text-decoration-line-through ms-1">{{ \App\Support\StoreCurrency::format($baseUnit, 0) }}</span>
                        @endif
                    </div>
                    @if ($itemSavings > 0)
                        <div class="small text-success">{{ $discountLabel ?? 'Offer applied' }}. Save {{ \App\Support\StoreCurrency::format($itemSavings, 0) }}</div>
                    @endif
                    <div class="small fw-semibold text-success">{{ \App\Support\StoreCurrency::format($item['subtotal'], 0) }}</div>
                </div>
                <div class="text-end">
                    <form method="POST" action="{{ $safeRouteUrl('storefront.cart.remove', '/cart/items/'.$item['product']->getRouteKey(), ['product' => $item['product']]) }}" class="js-cart-remove mb-2">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">×</button>
                    </form>
                    <div class="sf-stepper sf-stepper-sm">
                        <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="-1" data-product="{{ $item['product']->id }}">−</button>
                        <span class="sf-stepper-value">{{ $item['quantity'] }}</span>
                        <button type="button" class="sf-stepper-btn js-cart-adjust" data-delta="1" data-product="{{ $item['product']->id }}">+</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-5">
                <div class="fw-semibold mb-2">{{ config('ui_messages.empty_cart') }}</div>
                <p class="text-secondary small mb-0">Add items to see them here.</p>
            </div>
        @endforelse
    </div>

    <div class="sf-cart-footer">
        <div class="d-flex justify-content-between small mb-2">
            <span>Item total</span>
            <strong>{{ \App\Support\StoreCurrency::format($cartTotals['itemTotal'] ?? 0, 0) }}</strong>
        </div>
        @if ($offerSavings > 0)
            <div class="d-flex justify-content-between small mb-2 text-success">
                <span>Offer savings</span>
                <strong>{{ \App\Support\StoreCurrency::format($offerSavings, 0) }}</strong>
            </div>
        @endif
        @if (($cartTotals['tax'] ?? 0) > 0)
            <div class="d-flex justify-content-between small mb-2">
                <span>Tax</span>
                <strong>{{ \App\Support\StoreCurrency::format($cartTotals['tax'], 0) }}</strong>
            </div>
        @endif
        <a href="{{ $safeRouteUrl('storefront.cart', '/cart') }}" class="btn btn-danger w-100 rounded-pill">Go to Cart</a>
    </div>
</div>
