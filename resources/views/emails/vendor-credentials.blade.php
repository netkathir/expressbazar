<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vendor Panel Access</title>
</head>
<body>
    <p>Hello {{ $vendor->vendor_name }},</p>
    <p>Your Express Bazar vendor panel account has been created.</p>
    <p>
        Login URL: <a href="{{ $loginUrl }}">{{ $loginUrl }}</a><br>
        Email: {{ $vendor->email }}<br>
        Password: {{ $plainPassword }}
    </p>
    <p>Please sign in and keep these credentials secure.</p>
</body>
</html>
