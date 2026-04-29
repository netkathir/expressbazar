@forelse ($products as $product)
    @include('storefront.partials.product-card', ['product' => $product])
@empty
    <x-empty-state>{{ $emptyMessage ?? config('ui_messages.no_products') }}</x-empty-state>
@endforelse
