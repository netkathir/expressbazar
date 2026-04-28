@forelse ($products as $product)
    @include('storefront.partials.product-card', ['product' => $product])
@empty
    <div class="sf-empty-state">{{ $emptyMessage ?? 'No products found.' }}</div>
@endforelse
