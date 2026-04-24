<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ($title ?? null) ? $title.' | ' : '' }}{{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        body {
            font-family: Inter, system-ui, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(230, 98, 57, 0.18), transparent 32%),
                radial-gradient(circle at top right, rgba(15, 23, 42, 0.08), transparent 28%),
                linear-gradient(180deg, #fffaf6 0%, #f8fafc 100%);
            min-height: 100vh;
            color: #1f2937;
        }
        .user-nav {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.82);
        }
        .hero {
            border: 1px solid rgba(230, 98, 57, 0.12);
            background: rgba(255, 255, 255, 0.88);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            border-radius: 28px;
        }
        .pill {
            background: rgba(230, 98, 57, 0.12);
            color: #b84722;
        }
        .soft-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            background: rgba(255, 255, 255, 0.84);
            border-radius: 22px;
            box-shadow: 0 18px 36px rgba(15, 23, 42, 0.05);
        }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
