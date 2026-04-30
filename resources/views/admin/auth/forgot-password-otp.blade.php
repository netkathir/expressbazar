<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('admin_panel.brand.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/css/admin.css') }}">
</head>
<body class="admin-shell">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card shell-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="{{ asset('branding/logo-trimmed.png') }}" alt="Express Bazar" class="mb-3" style="max-width: 250px; width: 100%; height: auto; object-fit: contain;">
                        <h1 class="mb-0" style="font-size: 13px;">Verify admin OTP.</h1>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.password.verify-otp') }}" class="d-grid gap-3">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <div>
                            <label class="form-label">OTP Code</label>
                            <input type="text" name="otp_code" class="form-control" maxlength="10" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary">Verify OTP</button>
                    </form>

                    <form method="POST" action="{{ route('admin.password.send-otp') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit" class="btn btn-link px-0">Resend OTP</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/inline-validation.js') }}"></script>
</body>
</html>
