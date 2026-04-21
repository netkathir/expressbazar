@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="section-heading hero-inline">
            <div>
                <h1>{{ $title }}</h1>
                <p>Platform control center for users, vendors, products, orders, payments, and offers.</p>
            </div>
        </div>

        <div class="steps-grid">
            <article class="step-card"><span class="step-number">1</span><h3>Users</h3><p>{{ $users }}</p></article>
            <article class="step-card"><span class="step-number">2</span><h3>Vendors</h3><p>{{ $vendors }}</p></article>
            <article class="step-card"><span class="step-number">3</span><h3>Products</h3><p>{{ $products }}</p></article>
            <article class="step-card"><span class="step-number">4</span><h3>Revenue</h3><p>Rs. {{ number_format($revenue) }}</p></article>
        </div>
    </section>
@endsection
