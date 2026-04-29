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
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/css/admin.css') }}">
    @stack('head')
</head>
<body class="admin-shell">
    <div id="overlay" class="overlay"></div>

    <nav id="topbar" class="navbar bg-white border-bottom fixed-top topbar px-3">
        <div class="d-flex align-items-center gap-2">
            <button id="toggleBtn" class="btn btn-light btn-icon btn-sm d-none d-lg-inline-flex" type="button" aria-label="Toggle sidebar" aria-expanded="true">
                <i class="ti ti-layout-sidebar-left-expand"></i>
            </button>
            <button id="mobileBtn" class="btn btn-light btn-icon btn-sm d-lg-none" type="button" aria-label="Open sidebar" aria-expanded="false">
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
                @php
                    $adminUnreadNotifications = \Illuminate\Support\Facades\Schema::hasTable('notifications')
                        ? auth()->user()->unreadNotifications()->where('type', \App\Notifications\LowStockNotification::class)->latest()->limit(5)->get()
                        : collect();
                    $adminUnreadCount = \Illuminate\Support\Facades\Schema::hasTable('notifications')
                        ? auth()->user()->unreadNotifications()->where('type', \App\Notifications\LowStockNotification::class)->count()
                        : 0;
                @endphp
                <div class="dropdown">
                    <button class="btn btn-light btn-icon btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <i class="ti ti-bell"></i>
                        <span id="notification-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $adminUnreadCount ? '' : 'd-none' }}">{{ $adminUnreadCount }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2" style="min-width: 280px;">
                        <div class="small fw-semibold px-2 py-1">Notifications</div>
                        <div id="notification-list">
                            @forelse ($adminUnreadNotifications as $note)
                                <div class="dropdown-item-text small text-secondary px-2 py-2">{{ $note->data['message'] ?? 'Notification' }}</div>
                            @empty
                                <div class="dropdown-item-text small text-secondary px-2 py-2">No new notifications</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none text-dark">
                    <span class="sf-avatar sf-avatar-sm">
                        @if (auth()->user()->avatar_path)
                            <img src="{{ asset(auth()->user()->avatar_path) }}" alt="{{ auth()->user()->name }}">
                        @else
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        @endif
                    </span>
                    <span class="small fw-semibold">{{ auth()->user()->name }}</span>
                </a>
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
                <img src="{{ asset('branding/logo-trimmed.png') }}" alt="Express Bazar" class="admin-brand-logo">
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
    @auth
        <script>
            (function () {
                const countEl = document.getElementById('notification-count');
                const listEl = document.getElementById('notification-list');

                if (!countEl || !listEl) {
                    return;
                }

                setInterval(function () {
                    fetch('{{ route('admin.notification-alerts') }}', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    })
                        .then(function (response) {
                            return response.ok ? response.json() : null;
                        })
                        .then(function (data) {
                            if (!data) {
                                return;
                            }

                            countEl.textContent = data.count;
                            countEl.classList.toggle('d-none', data.count < 1);
                            listEl.innerHTML = data.items.length
                                ? data.items.map(function (item) {
                                    const div = document.createElement('div');
                                    div.className = 'dropdown-item-text small text-secondary px-2 py-2';
                                    div.textContent = item.message;
                                    return div.outerHTML;
                                }).join('')
                                : '<div class="dropdown-item-text small text-secondary px-2 py-2">No new notifications</div>';
                        })
                        .catch(function () {});
                }, 10000);
            })();
        </script>
    @endauth
    @stack('scripts')
</body>
</html>
