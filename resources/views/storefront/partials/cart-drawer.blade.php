@php($items = $cartItems ?? collect())
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
            <div class="sf-cart-item">
                <img src="{{ $item['product']->images->first() ? asset($item['product']->images->first()->image_path) : asset('admin-theme/assets/images/product-1.png') }}" alt="{{ $item['product']->product_name }}">
                <div class="flex-grow-1">
                    <div class="fw-semibold small">{{ $item['product']->product_name }}</div>
                    <div class="small text-secondary">{{ $item['product']->vendor?->vendor_name }}</div>
                    <div class="small fw-semibold text-success">₹{{ number_format($item['subtotal'], 0) }}</div>
                </div>
                <div class="text-end">
                    <form method="POST" action="{{ route('storefront.cart.remove', $item['product']) }}" class="js-cart-remove mb-2">
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
                <div class="fw-semibold mb-2">Your cart is empty</div>
                <p class="text-secondary small mb-0">Add items to see them here.</p>
            </div>
        @endforelse
    </div>

    <div class="sf-cart-footer">
        <div class="d-flex justify-content-between small mb-2">
            <span>Item total</span>
            <strong>₹{{ number_format($cartTotals['itemTotal'] ?? 0, 0) }}</strong>
        </div>
        <a href="{{ route('storefront.cart') }}" class="btn btn-danger w-100 rounded-pill">Go to Cart</a>
    </div>
</div>
