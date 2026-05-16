<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $isStorefrontHomeTitle = request()->routeIs('user.home')
            && ! request()->hasAny(['search', 'q', 'vendor_id', 'pincode', 'postcode']);
        $browserTitle = $isStorefrontHomeTitle
            ? 'Welcome to ExpressBazaar'
            : 'ExpressBazaar / '.($title ?? 'Home');
    @endphp
    <title>{{ $browserTitle }}</title>
    <script>
        (function () {
            const isHome = @json($isStorefrontHomeTitle);
            const visitKey = 'expressbazar.storefrontVisited';

            if (!isHome) {
                try {
                    sessionStorage.setItem(visitKey, '1');
                } catch (error) {}
                return;
            }

            try {
                if (sessionStorage.getItem(visitKey)) {
                    document.title = 'ExpressBazaar / Home';
                } else {
                    sessionStorage.setItem(visitKey, '1');
                }
            } catch (error) {}
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('storefront/css/storefront.css') }}">
    <link rel="stylesheet" href="{{ asset('storefront/css/custom.css') }}">
    <link rel="stylesheet" href="{{ asset('css/custom-overrides.css') }}">
    @stack('head')
</head>
@php
    $hideStorefrontHeader = request()->routeIs(
        'storefront.login',
        'storefront.login.store',
        'storefront.register',
        'storefront.register.store',
        'storefront.otp.*',
        'storefront.password.*'
    );
@endphp
<body class="storefront-shell {{ $hideStorefrontHeader ? 'storefront-auth-shell' : '' }}" data-cart-count="{{ $cartCount ?? 0 }}">
    <div id="pageLoader" class="sf-page-loader" role="status" aria-live="polite" aria-label="Loading">
        <span class="loader-ring"></span>
    </div>

    @unless ($hideStorefrontHeader)
        <header class="sf-topbar sticky-top">
            <div class="container-fluid px-3 px-lg-4">
                <div class="sf-searchbar">
                    <a href="{{ route('user.home') }}" class="sf-logo text-decoration-none">
                        <img src="{{ asset('branding/expressbazaar-logo.png') }}" alt="Express Bazar" class="sf-brand-logo">
                    </a>

                    <div class="sf-header-controls">
                        <button class="sf-location-btn js-open-location" type="button">
                            <i class="ti ti-map-pin me-1"></i>
                            <span class="js-location-label">{{ $locationLabel ?? 'Select Location' }}</span>
                            <i class="ti ti-chevron-down ms-1"></i>
                        </button>

                        <div class="dropdown sf-vendor-selector js-vendor-selector {{ empty($location ?? null) ? 'd-none' : '' }}">
                            <button class="sf-vendor-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ti ti-building-store me-1"></i>
                                <span class="js-selected-vendor-text">Vendors</span>
                                <i class="ti ti-chevron-down ms-1"></i>
                            </button>
                            <ul class="dropdown-menu sf-vendor-menu js-vendor-list">
                                <li class="dropdown-item text-muted">Loading...</li>
                            </ul>
                        </div>

                        <form action="{{ route('user.home') }}" method="GET" class="sf-search-form js-search-form">
                            <i class="ti ti-search"></i>
                            <input type="search" id="searchInput" class="js-search-input" name="search" placeholder="Search for products, categories or brands" value="{{ request('search', request('q')) }}" autocomplete="off">
                            @if (request()->filled('vendor_id'))
                                <input type="hidden" name="vendor_id" value="{{ request('vendor_id') }}">
                            @endif
                            <div class="sf-search-suggestions js-search-suggestions" hidden></div>
                        </form>
                    </div>

                    <div class="sf-actions">
                        @auth
                            @if (auth()->user()->role === 'customer')
                                <div class="dropdown">
                                    <button class="sf-action-link sf-profile-link" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-user-circle"></i>
                                        <span>Profile</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2">
                                        <a href="{{ route('storefront.account') }}" class="dropdown-item rounded-2">
                                            <i class="ti ti-user-circle me-2"></i>My Account
                                        </a>
                                        <a href="{{ route('user.home') }}#top-offers" class="dropdown-item rounded-2">
                                            <i class="ti ti-discount-2 me-2"></i>Offers
                                        </a>
                                        <a href="{{ route('storefront.addresses.index') }}" class="dropdown-item rounded-2">
                                            <i class="ti ti-map-pin me-2"></i>Address
                                        </a>
                                    </div>
                                </div>
                                <div class="dropdown">
                                    <button class="sf-action-link sf-alert-link position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                                        <i class="ti ti-bell-filled" aria-hidden="true"></i>
                                        <span id="notification-count" class="sf-cart-badge js-notification-count d-none">0</span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2" style="min-width: 280px;">
                                        <div class="d-flex align-items-center justify-content-between gap-2 px-2 py-1">
                                            <div class="small fw-semibold">Notifications</div>
                                            <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none js-notifications-clear-all d-none">Clear all</button>
                                        </div>
                                        <div id="notification-list">
                                            <div class="dropdown-item-text small text-secondary px-2 py-2">No notifications</div>
                                        </div>
                                    </div>
                                </div>
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
                        @endauth
                        <button class="sf-action-link js-open-cart" type="button">
                            <i class="ti ti-shopping-cart"></i>
                            <span>Cart</span>
                            <span class="sf-cart-badge js-cart-count {{ ($cartCount ?? 0) > 0 ? '' : 'd-none' }}">{{ $cartCount ?? 0 }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>
    @endunless

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

    @unless ($hideStorefrontHeader)
    <footer class="sf-footer">
        <div class="container-fluid px-3 px-lg-4">
            <div class="sf-footer-grid">
                <div class="sf-footer-brand">
                    <a href="{{ route('user.home') }}" class="sf-footer-logo text-decoration-none">
                        <img src="{{ asset('branding/expressbazaar-logo.png') }}" alt="Express Bazaar" class="sf-brand-logo sf-brand-logo-footer">
                    </a>
                    <address class="sf-footer-address">
                        <span>AMAZE FARMS LIMITED,</span>
                        <span>73 Colby Street,</span>
                        <span>Southampton,</span>
                        <span>SO16 9RU,</span>
                        <span>United Kingdom.</span>
                    </address>
                </div>

                <div class="sf-footer-col">
                    <h6>About Us</h6>
                    <a href="#">About Express Bazaar</a>
                    <a href="#">Terms &amp; Conditions</a>
                    <a href="#">Privacy Policy</a>
                </div>

                <div class="sf-footer-col">
                    <h6>Customer Service</h6>
                    <a href="{{ route('storefront.contact') }}">Contact Us</a>
                    <a href="#">FAQs</a>
                    <a href="#">Shipping Policy</a>
                    <a href="{{ auth()->check() && auth()->user()->role === 'customer' ? route('storefront.orders.index') : route('storefront.login') }}">Track Your Order</a>
                </div>

                <div class="sf-footer-col">
                    <h6>Categories</h6>
                    @foreach (($categories ?? collect())->take(5) as $category)
                        <a href="{{ route('storefront.category', $category) }}">{{ $category->category_name }}</a>
                    @endforeach
                    <a href="{{ route('user.home') }}#featured-sections">View All Categories</a>
                </div>

                <div class="sf-footer-col sf-footer-social-col">
                    <h6>Follow Us</h6>
                    <div class="sf-socials">
                        <a href="#" aria-label="Facebook"><i class="ti ti-brand-facebook"></i></a>
                        <a href="#" aria-label="Instagram"><i class="ti ti-brand-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="ti ti-brand-x"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="ti ti-brand-linkedin"></i></a>
                    </div>
                    <div class="sf-payment-row" aria-label="Payment methods">
                        <span>UPI</span>
                        <span>Visa</span>
                        <span>Mastercard</span>
                        <span>Stripe</span>
                    </div>
                </div>
            </div>

            <div class="sf-footer-bottom">
                <div class="sf-footer-copyright">&copy; {{ date('Y') }} Express Bazaar. All rights reserved.</div>
            </div>
        </div>
    </footer>
    @endunless

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
                    <div class="alert alert-danger border-0 rounded-4 mb-0 d-none js-location-alert" role="alert"></div>
                    <div class="location-autocomplete-wrapper">
                        <label class="form-label" for="locationSearch">Search delivery location</label>
                        <input
                            type="text"
                            id="locationSearch"
                            class="form-control js-location-search"
                            placeholder="Select delivery location"
                            autocomplete="off"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="false"
                            aria-controls="locationSuggestionBox"
                        >
                        <div id="locationSuggestionBox" class="js-location-suggestion-box" role="listbox" hidden></div>
                    </div>
                    <div>
                        <label class="form-label">Postcode / Zone code</label>
                        <input type="text" name="postcode" class="form-control" placeholder="Enter postcode to auto-detect zone">
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Country</label>
                            <select name="country_id" class="form-select js-country-select">
                                <option value="">Choose country</option>
                                @foreach (($countries ?? collect()) as $country)
                                    <option value="{{ $country->id }}">{{ $country->country_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">City</label>
                            <select name="city_id" class="form-select js-city-select">
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
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4 js-location-clear">Clear</button>
                        <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-primary rounded-pill px-4" type="submit">Save Location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="checkoutAuthModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Login required</h5>
                        <div class="text-secondary small">Please login or register before payment.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3 text-secondary">Your cart will be kept while you continue to your account.</p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('storefront.login') }}" class="btn btn-danger rounded-pill px-4">Login</a>
                        <a href="{{ route('storefront.register') }}" class="btn btn-outline-dark rounded-pill px-4">Register</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addressDeleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Delete address?</h5>
                        <div class="text-secondary small">This saved address will be removed from your account.</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0 text-secondary">Are you sure you want to delete this address?</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger rounded-pill px-4 js-confirm-address-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    @php
        $notificationsUrl = auth()->check() ? route('notifications.index', [], false) : null;
        $notificationReadUrlTemplate = auth()->check()
            ? route('notifications.read', ['id' => '__ID__'], false)
            : null;
        $notificationReadAllUrl = auth()->check() ? route('notifications.read-all', [], false) : null;
        $initialVendorsForHeader = collect($vendors ?? [])->map(function ($vendor) {
            return [
                'id' => data_get($vendor, 'id'),
                'name' => data_get($vendor, 'vendor_name', data_get($vendor, 'name')),
            ];
        })->values();
    @endphp

    <script>
        window.storefrontConfig = {
            cartAddUrlTemplate: @json(route('storefront.cart.add', ['product' => '__ID__'], false)),
            cartUpdateUrlTemplate: @json(route('storefront.cart.update', ['product' => '__ID__'], false)),
            cartRemoveUrlTemplate: @json(route('storefront.cart.remove', ['product' => '__ID__'], false)),
            cartClearUrl: @json(route('storefront.cart.clear', [], false)),
            cartMergeUrl: @json(route('storefront.cart.merge', [], false)),
            homeUrl: @json(route('user.home', [], false)),
            locationUrl: @json(route('storefront.location', [], false)),
            locationAutocompleteUrl: @json(route('storefront.location.autocomplete', [], false)),
            locationCitiesUrl: @json(route('storefront.location.cities', [], false)),
            locationZonesUrl: @json(route('storefront.location.zones', [], false)),
            vendorsByLocationUrl: @json(route('storefront.vendors-by-location', [], false)),
            searchSuggestionsUrl: @json(route('storefront.search.suggestions', [], false)),
            notificationsUrl: @json($notificationsUrl),
            notificationReadUrlTemplate: @json($notificationReadUrlTemplate),
            notificationReadAllUrl: @json($notificationReadAllUrl),
            logoutUrl: @json(route('storefront.logout', [], false)),
            uiMessages: @json(config('ui_messages')),
            initialLocation: @json($location ?? null),
            initialVendors: @json($initialVendorsForHeader),
            initialSelectedVendorId: @json((string) ($selectedVendorId ?? request('vendor_id', ''))),
            resetHomeVendorFilterOnLoad: @json(request()->routeIs('user.home') && request()->filled('vendor_id')),
            initialCartState: @json($cartState ?? []),
            guestCartMerged: @json((bool) session('guest_cart_merged')),
            currentUserRole: @json(auth()->user()->role ?? null),
            currentUserAvatar: @json(auth()->user()->avatar_path ?? null),
            csrfToken: @json(csrf_token()),
            storeCurrency: @json(\App\Support\StoreCurrency::jsConfig()),
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('storefront/js/storefront.js') }}"></script>
    <script src="{{ asset('js/inline-validation.js') }}"></script>
    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.js-password-toggle');
            if (!button) {
                return;
            }

            const targetId = button.dataset.target;
            const input = targetId ? document.getElementById(targetId) : null;
            if (!input) {
                return;
            }

            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('ti-eye', !shouldShow);
                icon.classList.toggle('ti-eye-off', shouldShow);
            }

            button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        });
    </script>
    @stack('scripts')
</body>
</html>
