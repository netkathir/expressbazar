@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">Home <span>›</span> {{ $category->category_name }}</nav>

            <div class="sf-category-layout">
                <aside class="sf-sidepanel sf-category-sidebar">
                    <h4 class="mb-3">{{ $category->category_name }}</h4>
                    <div class="sf-side-links">
                        <a href="{{ route('storefront.category', $category) }}" class="d-flex align-items-center gap-2 {{ request()->routeIs('storefront.category') ? 'active' : '' }}">
                            <img src="{{ asset($category->image_path ?: 'admin-theme/assets/images/product-1.png') }}" alt="{{ $category->category_name }}">
                            <span>All</span>
                        </a>
                        @foreach ($category->subcategories as $subcategory)
                            <a href="{{ route('storefront.subcategory', $subcategory) }}" class="d-flex align-items-center gap-2">
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
                            <p>Browse products across all subcategories in this category.</p>
                        </div>
                    </div>

                    <div class="sf-filter-row">
                        <button type="button" class="sf-filter-pill active">All</button>
                        @foreach ($category->subcategories as $subcategory)
                            <a href="{{ route('storefront.subcategory', $subcategory) }}" class="sf-filter-pill d-inline-flex align-items-center gap-2">
                                <img src="{{ asset($subcategory->image_path ?: 'admin-theme/assets/images/product-1.png') }}" alt="{{ $subcategory->subcategory_name }}" style="width:18px;height:18px;border-radius:6px;object-fit:cover;">
                                <span>{{ $subcategory->subcategory_name }}</span>
                            </a>
                        @endforeach
                        <button type="button" class="sf-filter-pill">Brand</button>
                        <button type="button" class="sf-filter-pill">Price</button>
                    </div>

                    <div class="sf-grid sf-product-grid">
                        @forelse ($products as $product)
                            @include('storefront.partials.product-card', ['product' => $product])
                        @empty
                            <div class="sf-empty-state">No products found.</div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </main>
@endsection
