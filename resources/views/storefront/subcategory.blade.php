@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">
                <a href="{{ route('user.home') }}">Home</a>
                <span>&rsaquo;</span>
                @if ($subcategory->category)
                    <a href="{{ route('storefront.category', $subcategory->category) }}">{{ $subcategory->category->category_name }}</a>
                @else
                    Category
                @endif
                <span>&rsaquo;</span>
                {{ $subcategory->subcategory_name }}
            </nav>

            <div class="sf-page-title">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="{{ \App\Support\StoreImage::subcategory($subcategory) }}" alt="{{ $subcategory->subcategory_name }}" style="width:54px;height:54px;border-radius:18px;border:1px solid var(--sf-border);object-fit:cover;" onerror="{{ \App\Support\StoreImage::onError('category') }}">
                        <h2 class="mb-0">Buy {{ $subcategory->subcategory_name }} Online</h2>
                    </div>
                    <p>All products from this subcategory are shown here.</p>
                </div>
            </div>

            <div class="sf-filter-row">
                <button type="button" class="sf-filter-pill">Brand</button>
                <button type="button" class="sf-filter-pill">Price</button>
                <button type="button" class="sf-filter-pill active">All products</button>
                <form method="GET" action="{{ route('storefront.subcategory', $subcategory) }}" class="d-inline-flex align-items-center gap-2">
                    @if (!empty($pincode ?? null))
                        <input type="hidden" name="pincode" value="{{ $pincode }}">
                    @endif
                    <select name="vendor_id" class="form-select form-select-sm rounded-pill js-filter-input" style="min-width: 220px;" onchange="if (!window.storefrontAjaxFilters) this.form.submit()">
                        <option value="">Vendors</option>
                        @forelse (($vendors ?? collect()) as $vendor)
                            <option value="{{ $vendor->id }}" @selected((string) ($selectedVendorId ?? request('vendor_id')) === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
                        @empty
                            <option disabled>{{ config('ui_messages.no_vendors') }}</option>
                        @endforelse
                    </select>
                </form>
            </div>

            <div class="sf-grid sf-product-grid js-product-list" id="product-list">
                @include('storefront.partials.product-grid', [
                    'products' => $products,
                    'emptyMessage' => config('ui_messages.no_products'),
                ])
            </div>
        </section>
    </main>
@endsection
