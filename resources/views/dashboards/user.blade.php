@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="section-heading hero-inline">
            <div>
                <h1>{{ $title }}</h1>
                <p>Customer workspace for orders, offers, wishlists, and account actions.</p>
            </div>
        </div>

        <div class="steps-grid">
            <article class="step-card"><span class="step-number">1</span><h3>Orders</h3><p>{{ $orders }}</p></article>
            <article class="step-card"><span class="step-number">2</span><h3>Wishlist</h3><p>{{ $wishlist }}</p></article>
            <article class="step-card"><span class="step-number">3</span><h3>Offers</h3><p>{{ $offers }}</p></article>
            <article class="step-card"><span class="step-number">4</span><h3>Rewards</h3><p>{{ $rewards }}</p></article>
        </div>
    </section>
@endsection
