@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-6">
                    <div class="sf-info-card p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-2">Create account</h1>
                        <p class="text-secondary mb-4">Register with your email and verify it with OTP.</p>
                        <form method="POST" action="{{ route('storefront.register.store') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="customer-register-password" required>
                                    <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="customer-register-password" aria-label="Show password">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Confirm password</label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" class="form-control" id="customer-register-password-confirmation" required>
                                    <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="customer-register-password-confirmation" aria-label="Show password">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-danger rounded-pill">Register</button>
                            </div>
                        </form>
                        <div class="mt-3 small text-secondary">
                            Already registered? <a href="{{ route('storefront.login') }}">Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
