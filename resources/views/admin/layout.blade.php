<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription ?? config('app.name') }}">
    <title>{{ $pageTitle ?? 'Admin' }}</title>
    <link rel="stylesheet" href="{{ asset('storefront.css') }}">
</head>
@php
    $pageHeading = trim(explode('|', $pageTitle ?? 'Admin')[0]);
@endphp
<body class="admin-body">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <span class="admin-brand-mark">
                    <img src="{{ asset('admin/assets/images/logo-icon.svg') }}" alt="ExpressBazar">
                </span>
                <span class="admin-brand-copy">
                    <strong>ExpressBazar</strong>
                    <span class="admin-brand-subtitle">Admin workspace</span>
                </span>
            </a>

            <div class="admin-sidebar-card">
                <span class="admin-sidebar-kicker">Workspace</span>
                <strong>Simple ecommerce control</strong>
                <p>Manage catalog, stock, orders, and vendor data from one clean dashboard.</p>
            </div>

            <nav class="admin-nav" aria-label="Admin navigation">
                <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">Dashboard</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.categories*') ? 'active' : '' }}" href="{{ route('admin.categories') }}">Categories</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.subcategories*') ? 'active' : '' }}" href="{{ route('admin.subcategories') }}">Subcategories</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.vendors') ? 'active' : '' }}" href="{{ route('admin.vendors') }}">Vendors</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.locations') ? 'active' : '' }}" href="{{ route('admin.locations') }}">Locations</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.products*') ? 'active' : '' }}" href="{{ route('admin.products') }}">Products</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.inventory*') ? 'active' : '' }}" href="{{ route('admin.inventory') }}">Inventory</a>
                <a class="admin-nav-link {{ request()->routeIs('admin.orders') ? 'active' : '' }}" href="{{ route('admin.orders') }}">Orders</a>
            </nav>

            <form class="admin-logout" method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button class="admin-nav-link admin-nav-button" type="submit">Logout</button>
            </form>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="admin-kicker">Ecommerce Control Center</p>
                    <h1>{{ $pageHeading }}</h1>
                    <p class="admin-topbar-copy">A cleaner admin workspace for catalog, inventory, and order operations.</p>
                </div>
                <div class="admin-topbar-actions">
                    <div class="admin-topbar-chip">
                        <span>Live state</span>
                        DB connected
                    </div>
                    <div class="admin-topbar-chip">
                        <span>Theme</span>
                        Latest dashboard UI
                    </div>
                </div>
            </header>

            <main class="admin-content">
                @if (session('status'))
                    <div class="admin-alert">{{ session('status') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
