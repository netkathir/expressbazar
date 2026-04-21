@extends('admin.layout')

@section('content')
    <div class="hero-panel" style="margin-bottom: 16px;">
        <div>
            <h1 style="margin: 0 0 8px;">Settings</h1>
            <p class="admin-muted" style="margin: 0;">Configure store basics, payments, shipping, and platform branding.</p>
        </div>
    </div>

    <section class="admin-grid cols-2">
        <div class="admin-card">
            <h2 style="margin-top: 0;">Store profile</h2>
            <p class="admin-muted">Name, logo, contact details, and support email.</p>
            <div class="admin-grid" style="gap: 12px; margin-top: 18px;">
                <div class="metric"><div class="label">Store name</div><div class="value" style="font-size:1.4rem;">ExpressBazar</div></div>
                <div class="metric"><div class="label">Support</div><div class="value" style="font-size:1.4rem;">help@expressbazar.com</div></div>
            </div>
        </div>

        <div class="admin-card">
            <h2 style="margin-top: 0;">Platform toggles</h2>
            <div class="admin-grid" style="gap: 12px; margin-top: 18px;">
                <div class="metric"><div class="label">Stripe payments</div><div class="value" style="font-size:1.4rem;">Enabled</div></div>
                <div class="metric"><div class="label">EposNow sync</div><div class="value" style="font-size:1.4rem;">Scheduled</div></div>
                <div class="metric"><div class="label">Coupons</div><div class="value" style="font-size:1.4rem;">Live</div></div>
                <div class="metric"><div class="label">Inventory alerts</div><div class="value" style="font-size:1.4rem;">Active</div></div>
            </div>
        </div>
    </section>
@endsection
