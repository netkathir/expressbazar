<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Vendor Panel Access</title>
</head>
<body>
    @include('emails.partials.logo')
    <p>Hello {{ $vendor->vendor_name }},</p>
    <p>Your Express Bazar vendor panel access details are below.</p>
    <p>
        Login URL: <a href="{{ $loginUrl }}">{{ $loginUrl }}</a><br>
        Email: {{ $vendor->email }}<br>
        @if ($plainPassword)
            Password: {{ $plainPassword }}
        @else
            Password: unchanged
        @endif
    </p>
    @if ($setupUrl)
        <p>You can set or reset your vendor panel password here: <a href="{{ $setupUrl }}">{{ $setupUrl }}</a></p>
    @endif
    <p>Please sign in and keep these credentials secure.</p>
</body>
</html>
