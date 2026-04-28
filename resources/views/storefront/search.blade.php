@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">Home <span>›</span> Search</nav>
            <div class="sf-section-header">
                <div>
                    <h3>Search Results</h3>
                    <p class="text-secondary mb-0">{{ !empty($requiresLocation ?? false) ? 'Enter your delivery location to see exact availability' : ($keyword ? 'Results for "'.$keyword.'"' : 'Popular products') }}</p>
                </div>
            </div>
            <div class="sf-grid js-product-list" id="product-list">
                @include('storefront.partials.product-grid', [
                    'products' => $searchResults,
                    'emptyMessage' => !empty($requiresLocation ?? false)
                        ? 'Enter your delivery location to see exact availability'
                        : (!empty($pincode ?? null) ? 'No products available in your area' : 'No products found for your search'),
                ])
            </div>
        </section>
    </main>
@endsection
