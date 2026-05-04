<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title.' | ' : '' }}{{ config('admin_panel.brand.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/css/admin.css') }}">
    @stack('head')
</head>
<body class="admin-shell">
    @php
        $vendorUser = auth('vendor')->user();
        $adminUser = auth()->user();
        $panelUser = $vendorUser ?: $adminUser;
        $isVendorPanel = $vendorUser !== null;
        $panelBrandName = $isVendorPanel ? 'Express Bazar Vendor' : config('admin_panel.brand.name');
        $panelNavigation = $isVendorPanel ? config('vendor_panel.navigation') : config('admin_panel.navigation');
    @endphp
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
                <div class="fw-semibold">{{ $panelBrandName }}</div>
            </div>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            <span class="badge rounded-pill badge-soft">
                <i class="ti ti-bolt me-1"></i>{{ $isVendorPanel ? 'Vendor panel' : 'Admin panel' }}
            </span>
            @unless ($isVendorPanel)
                <a href="{{ route('user.home') }}" class="btn btn-outline-secondary btn-sm">User panel</a>
            @endunless
            @if ($panelUser)
                @php
                    $stockUnreadNotifications = \Illuminate\Support\Facades\Schema::hasTable('notifications')
                        ? $panelUser->unreadNotifications()->where('type', \App\Notifications\LowStockNotification::class)->latest()->limit(5)->get()
                        : collect();
                    $stockUnreadCount = \Illuminate\Support\Facades\Schema::hasTable('notifications')
                        ? $panelUser->unreadNotifications()->where('type', \App\Notifications\LowStockNotification::class)->count()
                        : 0;
                @endphp
                <div class="dropdown">
                    <button class="btn btn-light btn-icon btn-sm position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                        <i class="ti ti-bell"></i>
                        <span id="notification-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $stockUnreadCount ? '' : 'd-none' }}">{{ $stockUnreadCount }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2" style="min-width: 280px;">
                        <div class="small fw-semibold px-2 py-1">Notifications</div>
                        <div id="notification-list">
                            @forelse ($stockUnreadNotifications as $note)
                                <div class="dropdown-item-text small text-secondary px-2 py-2">{{ $note->data['message'] ?? 'Notification' }}</div>
                            @empty
                                <div class="dropdown-item-text small text-secondary px-2 py-2">No new notifications</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="dropdown admin-profile-dropdown">
                    <button class="btn admin-profile-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open admin account menu">
                        <span class="sf-avatar sf-avatar-sm">
                            @if (! $isVendorPanel && $panelUser->avatar_path)
                                <img src="{{ asset($panelUser->avatar_path) }}" alt="{{ $panelUser->name }}">
                            @else
                                {{ strtoupper(substr($isVendorPanel ? $panelUser->vendor_name : $panelUser->name, 0, 1)) }}
                            @endif
                        </span>
                        <span class="admin-profile-copy">
                            <span class="admin-profile-name">{{ $isVendorPanel ? $panelUser->vendor_name : $panelUser->name }}</span>
                            <span class="admin-profile-role">{{ \Illuminate\Support\Str::headline($panelUser->role ?: ($isVendorPanel ? 'Vendor' : 'Administrator')) }}</span>
                        </span>
                        <i class="ti ti-chevron-down admin-profile-chevron" aria-hidden="true"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2 admin-profile-menu">
                        <a href="{{ $isVendorPanel ? route('vendor.dashboard') : route('admin.dashboard') }}" class="dropdown-item rounded-2">
                            <i class="ti ti-layout-dashboard me-2"></i>Dashboard
                        </a>
                        <form method="POST" action="{{ $isVendorPanel ? route('vendor.logout') : route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item rounded-2 text-danger">
                                <i class="ti ti-logout me-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </nav>

    <aside id="sidebar" class="sidebar">
        <div class="logo-area">
            <a href="{{ $isVendorPanel ? route('vendor.dashboard') : route('admin.dashboard') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                <img src="{{ asset('branding/logo-trimmed.png') }}" alt="Express Bazar" class="admin-brand-logo">
            </a>
        </div>

        <div class="nav flex-column py-3">
            @foreach ($panelNavigation as $group)
                <div class="px-4 pt-2 pb-1">
                    <small class="nav-text text-uppercase text-secondary fw-semibold">{{ $group['group'] }}</small>
                </div>

                @foreach ($group['items'] as $item)
                    @if (
                        ! $panelUser
                        || ($isVendorPanel && method_exists($panelUser, 'canAccessVendorRoute') && $panelUser->canAccessVendorRoute($item['route'], 'GET'))
                        || (! $isVendorPanel && method_exists($panelUser, 'canAccessAdminRoute') && $panelUser->canAccessAdminRoute($item['route'], 'GET'))
                    )
                        <a
                            class="nav-link {{ ($activeMenu ?? '') === ($item['active'] ?? '') ? 'active' : '' }}"
                            href="{{ route($item['route'], $item['params'] ?? []) }}"
                            title="{{ $item['label'] }}"
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
                <div class="admin-flash-backdrop" data-admin-flash>
                    <div class="alert alert-success admin-flash-message shadow-lg" role="alertdialog" aria-modal="true" aria-live="assertive">
                        <div class="d-flex align-items-start gap-3">
                            <i class="ti ti-circle-check fs-4" aria-hidden="true"></i>
                            <div class="flex-grow-1">{{ session('success') }}</div>
                            <button type="button" class="btn-close" data-admin-flash-close aria-label="Close notification"></button>
                        </div>
                    </div>
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    <div class="modal fade" id="adminDeleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-delete-confirm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-0 text-secondary" id="adminDeleteConfirmMessage">Are you sure you want to delete this item?</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="adminDeleteConfirmButton">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminBackConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-delete-confirm">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-semibold">Leave without Saving?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <p class="mb-0 text-secondary" id="adminBackConfirmMessage">Are you sure you want to go back without editing?</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">No</button>
                    <button type="button" class="btn btn-primary" id="adminBackConfirmButton">Yes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('admin-theme/js/admin.js') }}"></script>
    <script src="{{ asset('js/inline-validation.js') }}"></script>
    <script>
        (function () {
            const flash = document.querySelector('[data-admin-flash]');
            if (!flash) {
                return;
            }

            const close = () => {
                flash.classList.add('is-closing');
                setTimeout(() => flash.remove(), 180);
            };

            flash.querySelector('[data-admin-flash-close]')?.addEventListener('click', close);
            flash.addEventListener('click', (event) => {
                if (event.target === flash) {
                    close();
                }
            });

            setTimeout(close, 3000);
        })();
    </script>
    @if (! $isVendorPanel && auth()->check())
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
    @endif
    @stack('scripts')
</body>
</html>
