@extends('layouts.storefront')

@section('content')
    <main class="sf-page">
        <section class="container py-5">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-5">
                    <div class="sf-info-card p-4 p-md-5">
                        <h1 class="h3 fw-bold mb-2">Reset Password</h1>
                        <p class="text-secondary mb-4">Create a new password for <strong>{{ $email }}</strong>.</p>

                        <form method="POST" action="{{ route('storefront.password.reset') }}" class="row g-3" novalidate>
                            @csrf
                            <input type="hidden" name="email" value="{{ $email }}">
                            <div class="col-12">
                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="customer-reset-password" required>
                                    <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="customer-reset-password" aria-label="Show password">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </div>
                                @error('password')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" name="password_confirmation" class="form-control" id="customer-reset-password-confirmation" required>
                                    <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="customer-reset-password-confirmation" aria-label="Show password">
                                        <i class="ti ti-eye"></i>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12 d-grid">
                                <button class="btn btn-danger rounded-pill">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
