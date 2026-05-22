@php($sections = collect($sections ?? [])->filter(fn ($section) => ($section['products'] ?? collect())->isNotEmpty()))
@php($filterQuery = array_filter([
    'pincode' => $pincode ?? request('pincode'),
    'vendor_id' => $selectedVendorId ?? request('vendor_id'),
], fn ($value) => filled($value)))
@php($filterQueryString = http_build_query($filterQuery))
@php($safeRouteUrl = $safeRouteUrl ?? function (string $name, string $fallback, array $parameters = [], bool $absolute = true) {
    if (\Illuminate\Support\Facades\Route::has($name)) {
        return route($name, $parameters, $absolute);
    }

    return $absolute ? url($fallback) : (parse_url(url($fallback), PHP_URL_PATH) ?: $fallback);
})

@forelse ($sections as $section)
    @php($sectionCategory = $section['category'] ?? null)
    @php($sectionSubcategory = $section['subcategory'] ?? null)
    <div class="sf-product-section-row">
        <div class="sf-section-header">
            <div>
                <h3>{{ $section['title'] }}</h3>
                @if ($sectionSubcategory)
                    <p class="text-secondary mb-0">{{ $sectionCategory?->category_name }}</p>
                @else
                    <p class="text-secondary mb-0">Products from available vendors</p>
                @endif
            </div>
            @if ($sectionSubcategory)
                <a href="{{ $safeRouteUrl('storefront.subcategory', '/subcategories/'.$sectionSubcategory->getRouteKey().($filterQueryString ? '?'.$filterQueryString : ''), array_merge(['subcategory' => $sectionSubcategory], $filterQuery)) }}">See all</a>
            @elseif ($sectionCategory)
                <a href="{{ $safeRouteUrl('storefront.category', '/categories/'.$sectionCategory->getRouteKey().($filterQueryString ? '?'.$filterQueryString : ''), array_merge(['category' => $sectionCategory], $filterQuery)) }}">See all</a>
            @endif
        </div>
        <div class="sf-rail-wrap mb-4">
            <button type="button" class="sf-rail-arrow sf-rail-arrow-left js-rail-scroll" data-direction="-1" aria-label="Scroll {{ $section['title'] }} left">
                <i class="ti ti-chevron-left"></i>
            </button>
            <div class="sf-product-rail">
                @foreach ($section['products'] as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <button type="button" class="sf-rail-arrow sf-rail-arrow-right js-rail-scroll" data-direction="1" aria-label="Scroll {{ $section['title'] }} right">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>
    </div>
@empty
    <x-empty-state>{{ $emptyMessage ?? config('ui_messages.no_products') }}</x-empty-state>
@endforelse
