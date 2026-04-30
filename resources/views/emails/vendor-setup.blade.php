<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vendor Setup</title>
</head>
<body>
    <p>Hello {{ $vendor->vendor_name }},</p>
    <p>Your Express Bazar vendor panel account is ready.</p>
    <p>Complete your setup here: <a href="{{ $setupUrl }}">{{ $setupUrl }}</a></p>
    <p>If you already received credentials, you can still use this link to set a new password.</p>
</body>
</html>
