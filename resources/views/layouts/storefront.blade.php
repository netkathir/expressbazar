<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription ?? config('app.name') }}">
    <title>{{ $pageTitle ?? config('app.name', 'ExpressBazar') }}</title>
    <link rel="stylesheet" href="{{ asset('storefront.css') }}">
</head>
<body>
    <div class="site-shell">
        <header class="site-header">
            <div class="container header-grid">
                <div class="brand-wrap">
                    <a class="brand brand-wordmark" href="{{ route('home') }}" aria-label="ExpressBazar home">
                        <img class="brand-logo-icon" src="{{ asset('admin/assets/images/logo-icon.svg') }}" alt="">
                        <span class="brand-copy">
                            <span class="brand-name">ExpressBazar</span>
                            <span class="brand-subtitle">Quick grocery shopping</span>
                        </span>
                    </a>
                    <form class="location-box" method="GET" action="{{ route('home') }}">
                        <label class="location-select-wrap" aria-label="Select location">
                            <select name="location_id" class="location-select" onchange="this.form.submit()">
                                <option value="">Select Location</option>
                                @foreach (($locations ?? []) as $location)
                                    <option value="{{ $location['id'] }}" @selected((int) ($selectedLocationId ?? 0) === (int) $location['id'])>
                                        {{ $location['label'] }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="location-chevron">&#9662;</span>
                        </label>
                    </form>
                </div>

                <form class="search-bar search-bar-wide" method="GET" action="{{ route('home') }}">
                    <span class="search-icon">&#128269;</span>
                    <input
                        type="search"
                        name="q"
                        value="{{ request('q', $query ?? '') }}"
                        placeholder='Search for "amul butter"'
                        aria-label="Search products"
                    >
                </form>

                <div class="header-actions">
                    @guest
                        <a class="action-icon" href="{{ route('login') }}">
                            <span class="action-icon-mark">&#128100;</span>
                            <span>Login</span>
                        </a>
                    @endguest
                    @auth
                        <a class="action-icon" href="{{ route('orders.mine') }}">
                            <span class="action-icon-mark">&#128100;</span>
                            <span>Profile</span>
                        </a>
                    @endauth

                    <a class="action-icon" href="{{ route('cart.show') }}">
                        <span class="action-icon-mark cart-mark">&#128722;<span class="cart-badge">{{ $cartCount ?? 0 }}</span></span>
                        <span>Cart</span>
                    </a>
                </div>
            </div>
        </header>

        <nav class="category-strip">
            <div class="container chip-row">
                @foreach (($topTabs ?? $navItems ?? []) as $tab)
                    <a class="chip {{ !empty($tab['active']) ? 'active' : '' }}" href="{{ $tab['anchor'] ?? route('home') }}">
                        @if (! empty($tab['icon']))
                            <span class="chip-icon">{{ $tab['icon'] }}</span>
                        @endif
                        <span>{{ $tab['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </nav>

        <main class="site-main">
            @if (session('status'))
                <div class="container alert-banner">
                    {{ session('status') }}
                </div>
            @endif

            @yield('content')
        </main>

        <footer id="footer-links" class="site-footer">
            <div class="container footer-grid">
                <div>
                    <div class="footer-brand">{{ $brandName ?? config('app.name', 'ExpressBazar') }}</div>
                    <p>Zepto-style grocery shopping with location-based vendor discovery and a simple checkout flow.</p>
                </div>
                <div>
                    <h4>Trending searches</h4>
                    <ul>
                        @foreach (($footerLinks ?? []) as $link)
                            <li>{{ $link }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h4>Need help?</h4>
                    <ul>
                        <li>Browse vendors</li>
                        <li>Add to cart</li>
                        <li>Checkout</li>
                        <li>Admin panel</li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
