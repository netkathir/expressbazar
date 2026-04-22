<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ ($title ?? null) ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('storefront/css/storefront.css') }}">
    @stack('head')
</head>
<body class="storefront-shell" data-cart-count="{{ $cartCount ?? 0 }}">
    <header class="sf-topbar sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <div class="sf-searchbar">
                <a href="{{ route('user.home') }}" class="sf-logo text-decoration-none">
                    <img src="{{ asset('branding/logo-trimmed.png') }}" alt="Express Bazar" class="sf-brand-logo">
                </a>

                <button class="sf-location-btn js-open-location" type="button">
                    <i class="ti ti-map-pin me-1"></i>
                    <span class="js-location-label">{{ $locationLabel ?? 'Select Location' }}</span>
                    <i class="ti ti-chevron-down ms-1"></i>
                </button>

                <form action="{{ route('storefront.search') }}" method="GET" class="sf-search-form">
                    <i class="ti ti-search"></i>
                    <input type="search" name="q" placeholder="Search for products, categories or brands" value="{{ request('q') }}">
                </form>

                <div class="sf-actions">
                    @auth
                        @if (auth()->user()->role === 'customer')
                            <a href="{{ route('storefront.account') }}" class="sf-avatar-link text-decoration-none">
                                <span class="sf-avatar">
                                    @if (auth()->user()->avatar_path)
                                        <img src="{{ asset(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}">
                                    @else
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    @endif
                                </span>
                            </a>
                            <a href="{{ route('storefront.account') }}" class="sf-action-link">
                                <i class="ti ti-user-circle"></i>
                                <span>Account</span>
                            </a>
                            <form action="{{ route('storefront.logout') }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="sf-action-link">
                                    <i class="ti ti-logout"></i>
                                    <span>Logout</span>
                                </button>
                            </form>
                        @else
                            <a href="{{ route('admin.dashboard') }}" class="sf-action-link">
                                <i class="ti ti-shield-lock"></i>
                                <span>Admin</span>
                            </a>
                        @endif
                    @else
                        <a href="{{ route('storefront.login') }}" class="sf-action-link">
                            <i class="ti ti-user-circle"></i>
                            <span>Login</span>
                        </a>
                        <a href="{{ route('storefront.register') }}" class="sf-action-link">
                            <i class="ti ti-user-plus"></i>
                            <span>Register</span>
                        </a>
                    @endauth
                    <button class="sf-action-link js-open-cart" type="button">
                        <i class="ti ti-shopping-cart"></i>
                        <span>Cart</span>
                        <span class="sf-cart-badge js-cart-count">{{ $cartCount ?? 0 }}</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid px-3 px-lg-4 pt-3">
        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-3">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-3">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-3">
                <div class="fw-semibold mb-1">Please fix the following:</div>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    @yield('content')

    <footer class="sf-footer">
        <div class="container-fluid px-3 px-lg-4">
            <div class="sf-footer-grid">
                <div class="sf-footer-brand">
                    <a href="{{ route('user.home') }}" class="sf-footer-logo text-decoration-none">
                        <img src="{{ asset('branding/logo-trimmed.png') }}" alt="Express Bazaar" class="sf-brand-logo sf-brand-logo-footer">
                    </a>
                    <p class="sf-footer-address mb-3">
                        AMAZE FARMS LIMITED,<br>
                        73 Colby Street,<br>
                        Southampton,<br>
                        SO16 9RU,<br>
                        United Kingdom.
                    </p>
                    <div class="sf-socials">
                        <a href="#" aria-label="Facebook"><i class="ti ti-brand-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="ti ti-brand-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="ti ti-brand-x"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="ti ti-brand-linkedin"></i></a>
                    </div>
                </div>

                <div class="sf-footer-col">
                    <h6>About Us</h6>
                    <a href="#">About Express Bazaar</a>
                    <a href="#">Careers</a>
                    <a href="#">Press</a>
                    <a href="#">Terms &amp; Conditions</a>
                    <a href="#">Privacy Policy</a>
                </div>

                <div class="sf-footer-col">
                    <h6>Customer Service</h6>
                    <a href="#">Contact Us</a>
                    <a href="#">FAQs</a>
                    <a href="#">Shipping Policy</a>
                    <a href="#">Returns &amp; Refunds</a>
                    <a href="#">Track Your Order</a>
                </div>

                <div class="sf-footer-col">
                    <h6>Categories</h6>
                    @foreach (($categories ?? collect())->take(5) as $category)
                        <a href="{{ route('storefront.category', $category) }}">{{ $category->category_name }}</a>
                    @endforeach
                    <a href="{{ route('user.home') }}#featured-sections">View All Categories</a>
                </div>
            </div>

            <div class="sf-footer-bottom">
                <div>© {{ date('Y') }} Express Bazaar. All rights reserved.</div>
            </div>
        </div>
    </footer>

    <div class="sf-drawer-backdrop js-close-cart"></div>
    <aside class="sf-cart-drawer">
        <div class="js-cart-drawer">
            @include('storefront.partials.cart-drawer')
        </div>
    </aside>

    <div class="modal fade" id="locationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Select Delivery Location</h5>
                        <div class="text-secondary small">Browse by city first, then lock exact delivery only when you add to cart.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form class="modal-body d-grid gap-3 js-location-form" action="{{ route('storefront.location') }}" method="POST">
                    @csrf
                    <input type="hidden" name="force_clear" value="0">
                    <div>
                        <label class="form-label">Postcode / Zone code</label>
                        <input type="text" name="postcode" class="form-control" placeholder="Enter postcode to auto-detect zone">
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Country</label>
                            <select name="country_id" class="form-select js-country-select" required>
                                <option value="">Choose country</option>
                                @foreach (($countries ?? collect()) as $country)
                                    <option value="{{ $country->id }}">{{ $country->country_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">City</label>
                            <select name="city_id" class="form-select js-city-select" required>
                                <option value="">Choose city</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Zone</label>
                            <select name="zone_id" class="form-select js-zone-select">
                                <option value="">Optional exact zone</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary rounded-pill px-4" type="submit">Save Location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.storefrontConfig = {
            cartAddUrlTemplate: @json(route('storefront.cart.add', ['product' => '__ID__'])),
            cartUpdateUrlTemplate: @json(route('storefront.cart.update', ['product' => '__ID__'])),
            cartRemoveUrlTemplate: @json(route('storefront.cart.remove', ['product' => '__ID__'])),
            cartClearUrl: @json(route('storefront.cart.clear')),
            cartMergeUrl: @json(route('storefront.cart.merge')),
            locationUrl: @json(route('storefront.location')),
            locationCitiesUrl: @json(route('storefront.location.cities')),
            locationZonesUrl: @json(route('storefront.location.zones')),
            initialLocation: @json($location ?? null),
            initialCartState: @json($cartState ?? []),
            currentUserRole: @json(auth()->user()->role ?? null),
            currentUserAvatar: @json(auth()->user()->avatar_path ?? null),
            csrfToken: @json(csrf_token()),
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('storefront/js/storefront.js') }}"></script>
    @stack('scripts')
</body>
</html>
