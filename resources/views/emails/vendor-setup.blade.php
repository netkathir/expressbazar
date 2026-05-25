@include('emails.partials.logo')

<p>Hello {{ $vendor->vendor_name }},</p>
<p>Your Express Bazar vendor panel account is ready.</p>
<p>Complete your setup here: <a href="{{ $setupUrl }}">{{ $setupUrl }}</a></p>
<p>If you already received credentials, you can still use this link to set a new password.</p>
