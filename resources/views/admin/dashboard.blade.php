@extends('layouts.admin')

@section('content')
    <div class="row g-3 align-items-stretch mb-4">
        <div class="col-12 col-xl-8">
            <div class="hero-card p-4 p-md-5">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
                    <div>
                        <h1 class="display-6 fw-bold mb-3">Build the ecommerce admin first, then expand the storefront.</h1>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.module', ['module' => 'countries']) }}" class="btn btn-primary">Start with location master</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="h-100 soft-panel p-4">
                <div class="fw-semibold mb-3">Panel split</div>
                <div class="d-grid gap-3">
                    <div class="soft-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">User panel</div>
                                <small class="text-secondary">Customer storefront comes next</small>
                            </div>
                            <span class="badge text-bg-light">Separate</span>
                        </div>
                    </div>
                    <div class="soft-card p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">Admin panel</div>
                                <small class="text-secondary">This theme powers the control center</small>
                            </div>
                            <span class="badge text-bg-primary">Active</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach (config('admin_panel.stats') as $stat)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="metric-card h-100 p-4">
                    <div class="text-secondary small">{{ $stat['label'] }}</div>
                    <div class="display-6 fw-bold mb-1">{{ $stat['value'] }}</div>
                    <div class="text-secondary small">{{ $stat['note'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h4 mb-1">Module coverage</h2>
                </div>
            </div>

            <div class="row g-3">
                @foreach (config('admin_panel.modules') as $slug => $module)
                    <div class="col-12 col-md-6 col-xl-4">
                        <a href="{{ isset($module['crud_route']) ? route($module['crud_route']) : route('admin.module', ['module' => $slug]) }}" class="module-card h-100 d-block text-decoration-none">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <div class="text-secondary small mb-1">{{ $module['group'] }}</div>
                                    <h3 class="h5 mb-1 text-dark">{{ $module['title'] }}</h3>
                                </div>
                                <span class="badge text-bg-light">{{ isset($module['crud_route']) ? 'CRUD' : 'Ready' }}</span>
                            </div>
                            <p class="text-secondary mb-3">{{ $module['subtitle'] }}</p>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
