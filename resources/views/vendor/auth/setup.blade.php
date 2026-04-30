<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Express Bazar Vendor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.35.0/dist/tabler-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin-theme/css/admin.css') }}">
</head>
<body class="admin-shell">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-5">
                <div class="card shell-card p-4 p-md-5">
                    <div class="text-center mb-4">
                        <img src="{{ asset('branding/logo-trimmed.png') }}" alt="Express Bazar" class="mb-3" style="max-width: 250px; width: 100%; height: auto; object-fit: contain;">
                        <h1 class="mb-1" style="font-size: 16px;">Complete vendor setup</h1>
                        <p class="text-secondary mb-0">{{ $vendor->email }}</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('vendor.setup.update', $token) }}" class="d-grid gap-3">
                        @csrf
                        <div>
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <div>
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required minlength="8" autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-primary">Complete Setup</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('js/inline-validation.js') }}"></script>
</body>
</html>
