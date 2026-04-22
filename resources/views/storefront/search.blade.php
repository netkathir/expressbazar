@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-3">
            <nav class="sf-breadcrumbs">Home <span>›</span> Search</nav>
            <div class="sf-section-header">
                <div>
                    <h3>Search Results</h3>
                    <p class="text-secondary mb-0">{{ $keyword ? 'Results for "'.$keyword.'"' : 'Popular products' }}</p>
                </div>
            </div>
            <div class="sf-grid">
                @forelse ($searchResults as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @empty
                    <div class="sf-empty-state">No products found.</div>
                @endforelse
            </div>
        </section>
    </main>
@endsection
