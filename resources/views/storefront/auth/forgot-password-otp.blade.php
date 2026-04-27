@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-5">
                    <div class="sf-info-card p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-2">Verify OTP</h1>
                        <p class="text-secondary mb-4">Enter the code sent to <strong>{{ $email }}</strong>.</p>

                        <form method="POST" action="{{ route('storefront.password.verify-otp') }}" class="row g-3">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <div class="col-12">
                                <label class="form-label">OTP Code</label>
                                <input type="text" name="otp_code" class="form-control" maxlength="10" required>
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-danger rounded-pill">Verify</button>
                            </div>
                        </form>

                        <form method="POST" action="{{ route('storefront.password.send-otp') }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <button class="btn btn-link px-0">Resend OTP</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
