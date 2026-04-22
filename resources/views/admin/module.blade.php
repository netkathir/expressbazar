@extends('layouts.admin')

@section('content')
    <div class="card shell-card mb-4">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                <div>
                    <span class="badge rounded-pill badge-soft mb-3">{{ $module['group'] }}</span>
                    <h1 class="h2 fw-bold mb-2">{{ $module['title'] }}</h1>
                    <p class="text-secondary mb-0">{{ $module['subtitle'] }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Back to dashboard</a>
                    <a href="{{ route('user.home') }}" class="btn btn-primary">Open user panel</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-xl-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Objective</h2>
                    <p class="text-secondary mb-0">{{ $module['objective'] }}</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Impact</h2>
                    <ul class="list-unstyled mb-0 info-list">
                        @foreach ($module['impact'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Workflow</h2>
                    <ol class="workflow-list mb-0">
                        @foreach ($module['workflow'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Fields</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($module['fields'] as $field)
                            <span class="badge rounded-pill text-bg-light border">{{ $field }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Business Rules</h2>
                    <ul class="list-unstyled mb-0 info-list">
                        @foreach ($module['rules'] as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shell-card h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-semibold mb-3">Database touchpoints</h2>
                    <ul class="list-unstyled mb-0 info-list">
                        @foreach ($module['database'] as $table)
                            <li>{{ $table }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
