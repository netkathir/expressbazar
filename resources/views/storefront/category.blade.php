@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">Home <span>›</span> {{ $category->category_name }}</nav>

            <div class="sf-category-layout">
                <aside class="sf-sidepanel sf-category-sidebar">
                    <h4 class="mb-3">{{ $category->category_name }}</h4>
                    <div class="sf-side-links">
                        <a href="{{ route('storefront.category', $category) }}" class="{{ request()->routeIs('storefront.category') ? 'active' : '' }}">All</a>
                        @foreach ($category->subcategories as $subcategory)
                            <a href="{{ route('storefront.subcategory', $subcategory) }}">{{ $subcategory->subcategory_name }}</a>
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
                            <a href="{{ route('storefront.subcategory', $subcategory) }}" class="sf-filter-pill">{{ $subcategory->subcategory_name }}</a>
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
