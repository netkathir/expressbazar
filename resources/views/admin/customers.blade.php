@extends('admin.layout')

@section('content')
    <div class="hero-panel" style="margin-bottom: 16px;">
        <div>
            <h1 style="margin: 0 0 8px;">Customers</h1>
            <p class="admin-muted" style="margin: 0;">View customer activity, orders, and total spend.</p>
        </div>
    </div>

    <section class="admin-card">
        <table class="admin-table">
            <thead><tr><th>Customer</th><th>Email</th><th>Orders</th><th>Total spent</th></tr></thead>
            <tbody>
                @foreach ($customers as $customer)
                    <tr>
                        <td>
                            <div style="display:flex; gap: 12px; align-items:center;">
                                <img class="thumb round" src="{{ $customer['avatar'] }}" alt="{{ $customer['name'] }}">
                                <div style="font-weight: 700;">{{ $customer['name'] }}</div>
                            </div>
                        </td>
                        <td>{{ $customer['email'] }}</td>
                        <td>{{ $customer['orders'] }}</td>
                        <td>Rs. {{ number_format($customer['spent']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection
