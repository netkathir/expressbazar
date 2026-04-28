@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        @php($filterQuery = array_filter([
            'pincode' => $pincode ?? null,
            'vendor_id' => $selectedVendorId ?? request('vendor_id'),
        ], fn ($value) => filled($value)))
        @php($emptyMessage = !empty($pincode ?? null) ? 'No data available for this pincode' : 'No products found.')
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">Home <span>›</span> {{ $category->category_name }}</nav>

            <div class="sf-category-layout">
                <aside class="sf-sidepanel sf-category-sidebar">
                    <h4 class="mb-3">{{ $category->category_name }}</h4>
                    <div class="sf-side-links">
                        <a href="{{ route('storefront.category', array_merge(['category' => $category], $filterQuery)) }}" class="d-flex align-items-center gap-2 {{ empty($selectedSubcategory) ? 'active' : '' }}">
                            <img src="{{ asset($category->image_path ?: 'admin-theme/assets/images/product-1.png') }}" alt="{{ $category->category_name }}">
                            <span>All</span>
                        </a>
                        @foreach ($category->subcategories as $subcategory)
                            <a href="{{ route('storefront.category', array_merge(['category' => $category, 'subcategory' => $subcategory->id], $filterQuery)) }}" class="d-flex align-items-center gap-2 {{ ($selectedSubcategory?->id ?? null) === $subcategory->id ? 'active' : '' }}">
                                <img src="{{ asset($subcategory->image_path ?: 'admin-theme/assets/images/product-1.png') }}" alt="{{ $subcategory->subcategory_name }}">
                                <span>{{ $subcategory->subcategory_name }}</span>
                            </a>
                        @endforeach
                    </div>
                </aside>

                <section>
                    <div class="sf-page-title">
                        <div>
                            <h2>Buy {{ $category->category_name }} Online</h2>
                            <p>
                                @if ($selectedSubcategory)
                                    Showing only {{ $selectedSubcategory->subcategory_name }} products.
                                @else
                                    Browse products across all subcategories in this category.
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="sf-filter-row">
                        <a href="{{ route('storefront.category', array_merge(['category' => $category], $filterQuery)) }}" class="sf-filter-pill {{ empty($selectedSubcategory) ? 'active' : '' }}">All</a>
                        @foreach ($category->subcategories as $subcategory)
                            <a href="{{ route('storefront.category', array_merge(['category' => $category, 'subcategory' => $subcategory->id], $filterQuery)) }}" class="sf-filter-pill d-inline-flex align-items-center gap-2 {{ ($selectedSubcategory?->id ?? null) === $subcategory->id ? 'active' : '' }}">
                                <img src="{{ asset($subcategory->image_path ?: 'admin-theme/assets/images/product-1.png') }}" alt="{{ $subcategory->subcategory_name }}" style="width:18px;height:18px;border-radius:6px;object-fit:cover;">
                                <span>{{ $subcategory->subcategory_name }}</span>
                            </a>
                        @endforeach
                        <button type="button" class="sf-filter-pill">Brand</button>
                        <button type="button" class="sf-filter-pill">Price</button>
                        <form method="GET" action="{{ route('storefront.category', ['category' => $category]) }}" class="d-inline-flex align-items-center gap-2">
                            @if (!empty($pincode ?? null))
                                <input type="hidden" name="pincode" value="{{ $pincode }}">
                            @endif
                            @if (!empty($selectedSubcategory?->id))
                                <input type="hidden" name="subcategory" value="{{ $selectedSubcategory->id }}">
                            @endif
                            <select name="vendor_id" class="form-select form-select-sm rounded-pill js-filter-input" style="min-width: 220px;" onchange="if (!window.storefrontAjaxFilters) this.form.submit()">
                                <option value="">All Vendors</option>
                                @forelse (($vendors ?? collect()) as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) ($selectedVendorId ?? request('vendor_id')) === (string) $vendor->id)>{{ $vendor->vendor_name }}</option>
                                @empty
                                    <option disabled>No vendors available</option>
                                @endforelse
                            </select>
                        </form>
                    </div>

                    <div class="sf-grid sf-product-grid js-product-list" id="product-list">
                        @include('storefront.partials.product-grid', ['products' => $products, 'emptyMessage' => $emptyMessage])
                    </div>
                </section>
            </div>
        </section>
    </main>
@endsection
