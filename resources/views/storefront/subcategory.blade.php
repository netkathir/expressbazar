@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">
                Home <span>›</span> {{ $subcategory->category?->category_name ?? 'Category' }} <span>›</span> {{ $subcategory->subcategory_name }}
            </nav>

            <div class="sf-page-title">
                <div>
                    <h2>Buy {{ $subcategory->subcategory_name }} Online</h2>
                    <p>All products from this subcategory are shown here.</p>
                </div>
            </div>

            <div class="sf-filter-row">
                <button type="button" class="sf-filter-pill">Brand</button>
                <button type="button" class="sf-filter-pill">Price</button>
                <button type="button" class="sf-filter-pill active">All products</button>
            </div>

            <div class="sf-grid sf-product-grid">
                @forelse ($products as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @empty
                    <div class="sf-empty-state">No products found.</div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
