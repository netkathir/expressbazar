Hello {{ $vendor->vendor_name }},

Your Express Bazar vendor panel access details are below.

Login URL: {{ $loginUrl }}
Email: {{ $vendor->email }}
Password: {{ $plainPassword ?: 'unchanged' }}
@if ($setupUrl)

Set or reset your vendor panel password here: {{ $setupUrl }}
@endif

Please sign in and keep these credentials secure.
