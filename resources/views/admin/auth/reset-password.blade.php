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
                        <h1 class="mb-0" style="font-size: 13px;">Create a new admin password.</h1>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.password.reset') }}" class="d-grid gap-3">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <div>
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" id="admin-reset-password" required autofocus>
                                <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="admin-reset-password" aria-label="Show password">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" name="password_confirmation" class="form-control" id="admin-reset-password-confirmation" required>
                                <button class="btn btn-outline-secondary js-password-toggle" type="button" data-target="admin-reset-password-confirmation" aria-label="Show password">
                                    <i class="ti ti-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.js-password-toggle');
            if (!button) {
                return;
            }

            const targetId = button.dataset.target;
            const input = targetId ? document.getElementById(targetId) : null;
            if (!input) {
                return;
            }

            const shouldShow = input.type === 'password';
            input.type = shouldShow ? 'text' : 'password';

            const icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('ti-eye', !shouldShow);
                icon.classList.toggle('ti-eye-off', shouldShow);
            }

            button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
