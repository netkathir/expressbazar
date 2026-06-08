@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container-fluid px-3 px-lg-4 py-4">
            <nav class="sf-breadcrumbs">
                <a href="{{ url('/') }}">Home</a>
                <span>&rsaquo;</span>
                {{ $pageTitle }}
            </nav>

            <div class="sf-detail-grid">
                <div class="sf-info-card">
                    <p class="text-uppercase text-danger fw-semibold small mb-2">Express Bazaar</p>
                    <h1 class="h3 fw-bold mb-3">{{ $pageTitle }}</h1>
                    <p class="text-muted mb-0">{{ $intro }}</p>
                </div>

                <div class="sf-info-card">
                    <h4 class="mb-3">Quick Help</h4>
                    <dl class="sf-specs mb-0">
                        <dt>Support</dt><dd><a href="{{ route('storefront.contact') }}">Contact our team</a></dd>
                        <dt>Email</dt><dd>support@expressbazaar.local</dd>
                    </dl>
                </div>
            </div>

            <div class="sf-info-card mt-4">
                @foreach ($sections as $section)
                    <section class="{{ $loop->last ? '' : 'mb-4 pb-3 border-bottom' }}">
                        <h2 class="h5 fw-bold mb-2">{{ $section['heading'] }}</h2>
                        <p class="text-muted mb-0">{{ $section['body'] }}</p>
                    </section>
                @endforeach
            </div>
        </section>
    </main>
@endsection
