<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription ?? config('app.name') }}">
    <title>{{ $pageTitle ?? config('app.name', 'ExpressBazar') }}</title>
    <link rel="stylesheet" href="{{ asset('storefront.css') }}">
    <script defer src="{{ asset('storefront.js') }}"></script>
</head>
<body>
    <div class="app-shell">
        <header class="topbar">
            <div class="container header-row">
                <a class="brand" href="{{ route('home') }}">
                    <span class="brand-mark">E</span>
                    <span class="brand-copy">
                        <strong>{{ $brandName ?? config('app.name', 'ExpressBazar') }}</strong>
                        <small>Quick grocery & daily essentials</small>
                    </span>
                </a>

                <button class="location-pill" type="button" aria-label="Current delivery location">
                    <span class="dot"></span>
                    <span>{{ $location ?? 'Hyderabad, Telangana' }}</span>
                    <span class="chev">v</span>
                </button>

                <label class="searchbar" for="search">
                    <span class="search-icon">/</span>
                    <input id="search" type="search" placeholder='Search for "rice", "milk", or "kurkure"'>
                </label>

                <div class="header-actions">
                    <a class="icon-link" href="{{ route('checkout.show') }}">
                        <span class="icon-ring">O</span>
                        <span>Login</span>
                    </a>
                    <a class="cart-link" href="{{ route('cart.show') }}">
                        <span class="icon-ring">Bag</span>
                        <span>Cart</span>
                        <span class="badge">{{ $cartCount ?? 0 }}</span>
                    </a>
                </div>
            </div>
        </header>

        <nav class="category-nav">
            <div class="container nav-row">
                @foreach (($navItems ?? []) as $item)
                    <a class="nav-chip {{ ($activeNav ?? '') === $item['slug'] ? 'active' : '' }}" href="{{ $item['slug'] === 'all' ? route('home') : route('category.show', $item['slug']) }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>
        </nav>

        <main>
            @yield('content')
        </main>

        <footer class="footer">
            <div class="container footer-grid">
                <div>
                    <div class="footer-brand">{{ $brandName ?? config('app.name', 'ExpressBazar') }}</div>
                    <p class="muted">A Zepto-inspired shopping experience for daily essentials, fresh groceries, and fast checkout.</p>
                </div>
                <div>
                    <h4>Quick links</h4>
                    <ul class="footer-links">
                        @foreach (($footerLinks ?? []) as $link)
                            <li>{{ $link }}</li>
                        @endforeach
                    </ul>
                </div>
                <div>
                    <h4>Checkout flow</h4>
                    <ul class="footer-links">
                        <li>Browse categories</li>
                        <li>Add to cart</li>
                        <li>Choose delivery slot</li>
                        <li>Pay and track order</li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
