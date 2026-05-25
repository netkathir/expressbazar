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
<p>Please sign in and keep these credentials secure.</p>
