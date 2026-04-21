<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Register' }}</title>
    <link rel="stylesheet" href="{{ asset('storefront.css') }}">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <div class="auth-panel auth-panel-brand">
            <div class="brand-wordmark auth-brand">
                <span class="brand-word">ExpressBazar</span>
            </div>
            <h1>Start shopping faster</h1>
            <p>Create your account to place orders, view history, and keep your details saved.</p>
        </div>

        <div class="auth-panel auth-panel-form">
            <div class="section-copy">
                <span class="eyebrow">Customer register</span>
                <h2>Create your account</h2>
            </div>

            <form method="POST" action="{{ route('register.submit') }}" class="checkout-form">
                @csrf
                <label class="field">
                    <span>Name</span>
                    <input class="input" type="text" name="name" value="{{ old('name') }}" placeholder="Your name">
                </label>
                <label class="field">
                    <span>Email</span>
                    <input class="input" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
                </label>
                <label class="field">
                    <span>Password</span>
                    <input class="input" type="password" name="password" placeholder="Create password">
                </label>
                <label class="field">
                    <span>Confirm password</span>
                    <input class="input" type="password" name="password_confirmation" placeholder="Confirm password">
                </label>
                <div class="form-actions">
                    <a class="btn btn-outline" href="{{ route('login') }}">Already have an account?</a>
                    <button class="btn btn-primary" type="submit">Register</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
