<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body {
            font-family: Poppins, system-ui, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(163, 214, 92, 0.18), transparent 32%),
                radial-gradient(circle at top right, rgba(31, 122, 99, 0.10), transparent 28%),
                linear-gradient(180deg, #F9F9F9 0%, rgba(163, 214, 92, 0.12) 100%);
            min-height: 100vh;
            color: #333333;
        }
        .user-nav {
            backdrop-filter: blur(12px);
            background: rgba(249, 249, 249, 0.88);
        }
        .hero {
            border: 1px solid rgba(31, 122, 99, 0.14);
            background: rgba(249, 249, 249, 0.9);
            box-shadow: 0 24px 60px rgba(31, 122, 99, 0.08);
            border-radius: 28px;
        }
        .pill {
            background: rgba(163, 214, 92, 0.24);
            color: #1F7A63;
        }
        .soft-card {
            border: 1px solid rgba(31, 122, 99, 0.1);
            background: rgba(249, 249, 249, 0.9);
            border-radius: 22px;
            box-shadow: 0 18px 36px rgba(31, 122, 99, 0.06);
        }
        @media (max-width: 575.98px) {
            .user-nav .container {
                gap: .75rem;
            }
            .user-nav .navbar-brand {
                white-space: normal;
            }
            .hero,
            .soft-card {
                border-radius: 18px;
            }
            .hero .display-5 {
                font-size: 2rem;
                line-height: 1.15;
            }
            .hero .btn,
            .soft-card .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
