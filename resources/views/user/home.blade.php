@extends('layouts.user')

@section('content')
    <nav class="navbar navbar-expand-lg user-nav border-bottom sticky-top">
        <div class="container py-2">
            <a class="navbar-brand fw-bold" href="{{ route('user.home') }}">Express Bazar</a>
            <div class="ms-auto d-flex gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-dark btn-sm">Admin panel</a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="hero p-4 p-md-5 mb-4">
            <div class="row align-items-center g-4">
                <div class="col-12 col-lg-7">
                    <span class="badge rounded-pill pill mb-3">User panel</span>
                    <h1 class="display-5 fw-bold mb-3">Customer storefront comes after the admin foundation is ready.</h1>
                    <p class="lead text-secondary mb-4">
                        This route separates the user experience from the admin workspace so we can build the ecommerce
                        operations panel first without mixing concerns.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-dark">Go to admin panel</a>
                        <a href="#panel-split" class="btn btn-outline-secondary">See the split</a>
                    </div>
                </div>
                <div class="col-12 col-lg-5">
                    <div class="soft-card p-4">
                        <div class="fw-semibold mb-2">Current focus</div>
                        <div class="display-6 fw-bold">{{ $moduleCount }}</div>
                        <div class="text-secondary">Admin modules mapped from your workflow document</div>
                    </div>
                </div>
            </div>
        </div>

        <div id="panel-split" class="row g-3">
            <div class="col-12 col-md-6">
                <div class="soft-card p-4 h-100">
                    <div class="fw-semibold mb-2">User panel</div>
                    <p class="text-secondary mb-0">Customer browsing, cart, checkout and order tracking will live here.</p>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="soft-card p-4 h-100">
                    <div class="fw-semibold mb-2">Admin panel</div>
                    <p class="text-secondary mb-0">Vendor, location, catalog, inventory, orders, payments and system controls are in the themed workspace.</p>
                </div>
            </div>
        </div>
    </main>
@endsection
