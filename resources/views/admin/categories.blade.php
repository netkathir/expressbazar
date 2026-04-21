@extends('admin.layout')

@section('content')
    <div class="hero-panel" style="margin-bottom: 16px;">
        <div>
            <h1 style="margin: 0 0 8px;">Categories</h1>
            <p class="admin-muted" style="margin: 0;">Organize the catalog into clear retail merchandising groups.</p>
        </div>
        <a class="btn btn-primary" href="#">Add Category</a>
    </div>

    <section class="admin-grid cols-3">
        @foreach ($categories as $category)
            <div class="admin-card">
                <img src="{{ $category['image'] }}" alt="{{ $category['name'] }}" style="width:100%; height:180px; object-fit:cover; border-radius:18px; border:1px solid var(--border);">
                <div style="display:flex; justify-content:space-between; gap: 12px; margin-top: 14px; align-items:flex-start;">
                    <div>
                        <h3 style="margin: 0 0 6px;">{{ $category['name'] }}</h3>
                        <p class="admin-muted" style="margin: 0;">{{ $category['products'] }} products</p>
                    </div>
                    <span class="admin-badge">{{ $category['slug'] }}</span>
                </div>
            </div>
        @endforeach
    </section>
@endsection
