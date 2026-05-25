Hello {{ $vendor->vendor_name }},

Your Express Bazar vendor panel access details are below.

Login URL: {{ $loginUrl }}
Email: {{ $vendor->email }}
Password: {{ $plainPassword ?: 'unchanged' }}

Please sign in and keep these credentials secure.
