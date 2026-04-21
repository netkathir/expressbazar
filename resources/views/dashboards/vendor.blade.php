@extends('layouts.storefront')

@section('content')
    <section class="container section-block">
        <div class="section-heading hero-inline">
            <div>
                <h1>{{ $title }}</h1>
                <p>Vendor workspace for inventory, offers, and order management.</p>
            </div>
        </div>

        <div class="steps-grid">
            <article class="step-card"><span class="step-number">1</span><h3>Products</h3><p>{{ $products }}</p></article>
            <article class="step-card"><span class="step-number">2</span><h3>Orders</h3><p>{{ $orders }}</p></article>
            <article class="step-card"><span class="step-number">3</span><h3>Sales</h3><p>Rs. {{ number_format($sales) }}</p></article>
            <article class="step-card"><span class="step-number">4</span><h3>Stock alerts</h3><p>{{ $stockAlerts }}</p></article>
        </div>
    </section>
@endsection
