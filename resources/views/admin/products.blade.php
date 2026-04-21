@extends('admin.layout')

@section('content')
    <div class="section-title">
        <div class="section-copy">
            <h1>Vendor Products</h1>
            <p>Price and stock are stored per vendor product mapping.</p>
        </div>
    </div>

    <section class="grid-2">
        @foreach ($products as $product)
            <div class="highlight-card">
                <img src="{{ $product['image'] }}" alt="{{ $product['product_name'] }}" style="width:100%; border-radius:18px; margin-bottom:12px;">
                <h3>{{ $product['product_name'] }}</h3>
                <p>Vendor: {{ $product['vendor_name'] }}</p>
                <p>Price: Rs. {{ $product['price'] }}</p>
                <p>Stock: {{ $product['stock'] }}</p>
            </div>
        @endforeach
    </section>
@endsection
