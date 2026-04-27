@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-5">
                    <div class="sf-info-card p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-2">Login</h1>
                        <p class="text-secondary mb-4">Access your cart, orders and saved addresses.</p>
                        <form method="POST" action="{{ route('storefront.login.store') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-danger rounded-pill">Login</button>
                            </div>
                        </form>
                        <div class="mt-3 small text-secondary">
                            <a href="{{ route('storefront.password.request') }}">Forgot password?</a>
                        </div>
                        <div class="mt-3 small text-secondary">
                            New here? <a href="{{ route('storefront.register') }}">Create an account</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
