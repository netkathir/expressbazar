<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Login' }}</title>
    <link rel="stylesheet" href="{{ asset('storefront.css') }}">
</head>
<body class="auth-page">
    <div class="auth-shell">
        <div class="auth-panel auth-panel-brand">
            <div class="brand-wordmark auth-brand">
                <span class="brand-word">ExpressBazar</span>
            </div>
            <h1>Welcome back</h1>
            <p>Sign in to track your orders, save your delivery details, and checkout faster.</p>
        </div>

        <div class="auth-panel auth-panel-form">
            <div class="section-copy">
                <span class="eyebrow">Customer login</span>
                <h2>Login to your account</h2>
            </div>

            <form method="POST" action="{{ route('login.submit') }}" class="checkout-form">
                @csrf
                <label class="field">
                    <span>Email</span>
                    <input class="input" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
                </label>
                <label class="field">
                    <span>Password</span>
                    <input class="input" type="password" name="password" placeholder="Enter password">
                </label>
                <div class="form-actions">
                    <a class="btn btn-outline" href="{{ route('register') }}">Create account</a>
                    <button class="btn btn-primary" type="submit">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
