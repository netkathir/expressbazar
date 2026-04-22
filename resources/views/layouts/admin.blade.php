<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title.' | ' : '' }}{{ config('admin_panel.brand.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin-theme/css/admin.css') }}">
    @stack('head')
</head>
<body class="admin-shell">
    <div id="overlay" class="overlay"></div>

    <nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
        <div class="d-flex align-items-center gap-2">
            <button id="toggleBtn" class="btn btn-light btn-icon btn-sm d-none d-lg-inline-flex" type="button" aria-label="Toggle sidebar">
                <i class="ti ti-layout-sidebar-left-expand"></i>
            </button>
            <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none" type="button" aria-label="Open sidebar">
                <i class="ti ti-layout-sidebar-left-expand"></i>
            </button>
            <div>
                <div class="fw-semibold">{{ config('admin_panel.brand.name') }}</div>
                <small class="text-secondary">{{ config('admin_panel.brand.tagline') }}</small>
            </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="badge rounded-pill badge-soft">
                <i class="ti ti-bolt me-1"></i>Admin panel
            </span>
            <a href="{{ route('user.home') }}" class="btn btn-outline-secondary btn-sm">User panel</a>
            @auth
                <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-dark btn-sm">Logout</button>
                </form>
            @endauth
        </div>
    </nav>

    <aside id="sidebar" class="sidebar">
        <div class="logo-area">
            <a href="{{ route('admin.dashboard') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ asset('admin-theme/assets/images/logo-icon.svg') }}" alt="Logo" width="24" height="24">
                <span class="logo-text">
                    <img src="{{ asset('admin-theme/assets/images/logo.svg') }}" alt="Express Bazar" height="24">
                </span>
            </a>
        </div>

        <div class="nav flex-column py-3">
            @php($currentUser = auth()->user())
            @foreach (config('admin_panel.navigation') as $group)
                <div class="px-4 pt-2 pb-1">
                    <small class="nav-text text-uppercase text-secondary fw-semibold">{{ $group['group'] }}</small>
                </div>

                @foreach ($group['items'] as $item)
                    @if (! $currentUser || ! method_exists($currentUser, 'canAccessAdminRoute') || $currentUser->canAccessAdminRoute($item['route'], 'GET'))
                        <a
                            class="nav-link {{ ($activeMenu ?? '') === ($item['active'] ?? '') ? 'active' : '' }}"
                            href="{{ route($item['route'], $item['params'] ?? []) }}"
                        >
                            <i class="ti ti-{{ $item['icon'] }}"></i>
                            <span class="nav-text">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            @endforeach
        </div>
    </aside>

    <main id="content" class="content py-4">
        <div class="container-fluid">
            @if (session('success'))
                <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger border-0 shadow-sm">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('admin-theme/js/admin.js') }}"></script>
    @stack('scripts')
</body>
</html>
