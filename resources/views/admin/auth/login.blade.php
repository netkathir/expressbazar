<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $pageDescription ?? config('app.name') }}">
    <title>{{ $pageTitle ?? 'Admin Login' }}</title>
    <link rel="stylesheet" href="{{ asset('storefront.css') }}">
    <style>
        :root {
            --login-bg: #f5f7fb;
            --login-surface: #ffffff;
            --login-border: #e5eaf2;
            --login-text: #10213a;
            --login-muted: #64748b;
            --login-accent: #2f63f6;
            --login-accent-soft: #ebf2ff;
            --login-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: var(--login-text);
            background:
                radial-gradient(circle at top left, rgba(47, 99, 246, 0.12), transparent 26%),
                radial-gradient(circle at bottom right, rgba(124, 58, 237, 0.08), transparent 24%),
                var(--login-bg);
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .login-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.12fr) minmax(420px, 0.88fr);
        }

        .login-hero {
            padding: 40px 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-card {
            width: min(100%, 700px);
            min-height: 720px;
            border-radius: 34px;
            overflow: hidden;
            background:
                linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,0)),
                linear-gradient(135deg, #fdfcff 0%, #eef4ff 46%, #ffffff 100%);
            border: 1px solid rgba(229, 234, 242, 0.95);
            box-shadow: var(--login-shadow);
            display: grid;
            grid-template-rows: 1fr auto;
        }

        .hero-visual {
            position: relative;
            padding: 42px 38px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 30% 20%, rgba(47, 99, 246, 0.14), transparent 24%),
                radial-gradient(circle at 80% 20%, rgba(124, 58, 237, 0.12), transparent 18%),
                linear-gradient(180deg, #ffffff 0%, #f6f8fd 100%);
        }

        .hero-visual img {
            width: 100%;
            max-height: 540px;
            object-fit: contain;
        }

        .hero-copy {
            padding: 18px 34px 34px;
        }

        .hero-copy h1 {
            margin: 0;
            font-size: clamp(2.4rem, 4vw, 4.6rem);
            line-height: 0.95;
            letter-spacing: -0.06em;
            color: #101828;
        }

        .hero-copy p {
            max-width: 18ch;
            margin: 14px 0 0;
            color: var(--login-muted);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .login-panel {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 34px;
        }

        .login-card {
            width: min(100%, 480px);
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(229, 234, 242, 0.95);
            border-radius: 30px;
            box-shadow: var(--login-shadow);
            padding: 34px;
            backdrop-filter: blur(16px);
        }

        .login-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .login-brand img {
            width: 46px;
            height: 46px;
            object-fit: contain;
        }

        .login-brand strong {
            display: block;
            font-size: 1.1rem;
            line-height: 1.2;
        }

        .login-brand span {
            color: var(--login-muted);
            font-size: 0.92rem;
        }

        .login-card h2 {
            margin: 14px 0 10px;
            font-size: 2.3rem;
            line-height: 1.04;
            letter-spacing: -0.05em;
        }

        .login-card > p {
            margin: 0 0 22px;
            color: var(--login-muted);
            line-height: 1.75;
            font-size: 1rem;
        }

        .field {
            display: block;
            margin-bottom: 16px;
        }

        .field span {
            display: block;
            margin-bottom: 8px;
            font-size: 0.92rem;
            font-weight: 700;
            color: #1e293b;
        }

        .field input {
            width: 100%;
            min-height: 50px;
            padding: 14px 16px;
            border-radius: 16px;
            border: 1px solid var(--login-border);
            background: #fff;
            color: var(--login-text);
            font: inherit;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        }

        .field input:focus {
            border-color: var(--login-accent);
            box-shadow: 0 0 0 4px rgba(47, 99, 246, 0.12);
        }

        .remember-row {
            display: flex;
            gap: 10px;
            align-items: center;
            margin: 6px 0 0;
        }

        .remember-row input {
            width: 18px;
            height: 18px;
            min-height: 18px;
            margin: 0;
            accent-color: var(--login-accent);
        }

        .remember-row span {
            margin: 0;
            color: #1e293b;
            font-size: 0.93rem;
            font-weight: 600;
        }

        .actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            padding: 0 18px;
            border-radius: 16px;
            border: 1px solid var(--login-border);
            background: #fff;
            color: #0f172a;
            text-decoration: none;
            font-weight: 800;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease, border-color .18s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
        }

        .btn.submit {
            min-width: 116px;
            background: linear-gradient(135deg, #2f63f6, #4f7cfb);
            color: #fff;
            border-color: transparent;
            box-shadow: 0 14px 24px rgba(47, 99, 246, 0.22);
        }

        .btn.submit:hover {
            box-shadow: 0 18px 28px rgba(47, 99, 246, 0.26);
        }

        .btn.secondary {
            background: #fff;
            color: #1e293b;
        }

        .error {
            color: #b91c1c;
            margin: 0 0 12px;
            font-size: 0.92rem;
        }

        .help-box {
            margin-top: 20px;
            padding: 16px 18px;
            border-radius: 18px;
            background: var(--login-accent-soft);
            border: 1px solid rgba(47, 99, 246, 0.12);
            color: #1e3a8a;
            line-height: 1.65;
            font-size: 0.93rem;
        }

        .help-box strong {
            display: block;
            margin-top: 4px;
        }

        @media (max-width: 1040px) {
            .login-shell {
                grid-template-columns: 1fr;
            }

            .login-hero {
                padding-bottom: 0;
            }

            .hero-card {
                min-height: auto;
            }
        }

        @media (max-width: 640px) {
            .login-hero,
            .login-panel {
                padding: 14px;
            }

            .hero-card,
            .login-card {
                border-radius: 24px;
            }

            .hero-copy,
            .login-card {
                padding: 22px;
            }

            .actions {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="login-shell">
        <div class="login-hero">
            <div class="hero-card">
                <div class="hero-visual">
                    <img src="{{ asset('organic/images/img-login.jpg') }}" alt="Ecommerce admin preview">
                </div>
                <div class="hero-copy">
                    <h1>Manage your ecommerce store with clarity.</h1>
                    <p>Track products, categories, orders, and customers from one professional admin workspace.</p>
                </div>
            </div>
        </div>

        <div class="login-panel">
            <form class="login-card" method="POST" action="{{ route('admin.login.store') }}">
                @csrf
                <div class="login-brand">
                    <img src="{{ asset('admin/assets/images/logo-icon.svg') }}" alt="ExpressBazar">
                    <div>
                        <strong>ExpressBazar Admin</strong>
                        <span>Secure back office login</span>
                    </div>
                </div>

                <h2>Admin Login</h2>
                <p>Sign in to manage ecommerce operations and sample store data.</p>

                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror

                <label class="field">
                    <span>Email</span>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="admin@expressbazar.com" required autofocus>
                </label>

                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" placeholder="password" required>
                </label>

                <label class="field remember-row">
                    <input type="checkbox" name="remember" value="1">
                    <span>Remember me</span>
                </label>

                <div class="actions">
                    <a class="btn secondary" href="{{ route('home') }}">Back to site</a>
                    <button class="btn submit" type="submit">Login</button>
                </div>

                <div class="help-box">
                    Default demo login
                    <strong>admin@expressbazar.com / password</strong>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
