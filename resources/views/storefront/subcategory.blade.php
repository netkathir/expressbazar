@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">
                Home <span>›</span> {{ $subcategory->category?->category_name ?? 'Category' }} <span>›</span> {{ $subcategory->subcategory_name }}
            </nav>

            <div class="sf-page-title">
                <div>
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <img src="{{ asset($subcategory->image_path ?: 'admin-theme/assets/images/product-1.png') }}" alt="{{ $subcategory->subcategory_name }}" style="width:54px;height:54px;border-radius:18px;border:1px solid var(--sf-border);object-fit:cover;">
                        <h2 class="mb-0">Buy {{ $subcategory->subcategory_name }} Online</h2>
                    </div>
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
