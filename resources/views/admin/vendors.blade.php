@extends('admin.layout')

@section('content')
    <div class="section-title">
        <div class="section-copy">
            <h1>Vendors</h1>
            <p>Manage vendor stores and their mapped locations.</p>
        </div>
    </div>

    <section class="summary-card">
        @foreach ($vendors as $vendor)
            <div class="summary-row" style="border-bottom:1px solid #e6ebf2;">
                <div>
                    <strong>{{ $vendor['name'] }}</strong>
                    <div class="muted">{{ $vendor['address'] }}</div>
                </div>
                <div class="muted">{{ implode(', ', $vendor['locations']) }}</div>
            </div>
        @endforeach
    </section>
@endsection
