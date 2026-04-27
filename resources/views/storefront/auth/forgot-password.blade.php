@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-5">
                    <div class="sf-info-card p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-2">Forgot Password</h1>
                        <p class="text-secondary mb-4">Enter your email and we will send you an OTP to reset your password.</p>

                        <form method="POST" action="{{ route('storefront.password.send-otp') }}" class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-danger rounded-pill">Send OTP</button>
                            </div>
                        </form>

                        <div class="mt-3 small text-secondary">
                            Remembered your password? <a href="{{ route('storefront.login') }}">Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
