@extends('layouts.admin')

@section('content')
    @php
        $currencyDisplay = [
            'AED' => ['symbol' => 'د.إ', 'name' => 'United Arab Emirates Dirham'],
            'AFN' => ['symbol' => '؋', 'name' => 'Afghan Afghani'],
            'ALL' => ['symbol' => 'L', 'name' => 'Albanian Lek'],
            'AMD' => ['symbol' => '֏', 'name' => 'Armenian Dram'],
            'AOA' => ['symbol' => 'Kz', 'name' => 'Angolan Kwanza'],
            'ARS' => ['symbol' => '$', 'name' => 'Argentine Peso'],
            'AUD' => ['symbol' => '$', 'name' => 'Australian Dollar'],
            'AZN' => ['symbol' => '₼', 'name' => 'Azerbaijani Manat'],
            'BDT' => ['symbol' => '৳', 'name' => 'Bangladeshi Taka'],
            'BGN' => ['symbol' => 'лв', 'name' => 'Bulgarian Lev'],
            'BHD' => ['symbol' => '.د.ب', 'name' => 'Bahraini Dinar'],
            'BRL' => ['symbol' => 'R$', 'name' => 'Brazilian Real'],
            'BSD' => ['symbol' => '$', 'name' => 'Bahamian Dollar'],
            'BYN' => ['symbol' => 'Br', 'name' => 'Belarusian Ruble'],
            'CAD' => ['symbol' => '$', 'name' => 'Canadian Dollar'],
            'CHF' => ['symbol' => 'Fr', 'name' => 'Swiss Franc'],
            'CLP' => ['symbol' => '$', 'name' => 'Chilean Peso'],
            'CNY' => ['symbol' => '¥', 'name' => 'Chinese Yuan'],
            'COP' => ['symbol' => '$', 'name' => 'Colombian Peso'],
            'CZK' => ['symbol' => 'Kč', 'name' => 'Czech Koruna'],
            'DKK' => ['symbol' => 'kr', 'name' => 'Danish Krone'],
            'DZD' => ['symbol' => 'د.ج', 'name' => 'Algerian Dinar'],
            'EGP' => ['symbol' => '£', 'name' => 'Egyptian Pound'],
            'EUR' => ['symbol' => '€', 'name' => 'Euro'],
            'GBP' => ['symbol' => '£', 'name' => 'British Pound'],
            'GEL' => ['symbol' => '₾', 'name' => 'Georgian Lari'],
            'GHS' => ['symbol' => '₵', 'name' => 'Ghanaian Cedi'],
            'HKD' => ['symbol' => '$', 'name' => 'Hong Kong Dollar'],
            'HUF' => ['symbol' => 'Ft', 'name' => 'Hungarian Forint'],
            'IDR' => ['symbol' => 'Rp', 'name' => 'Indonesian Rupiah'],
            'ILS' => ['symbol' => '₪', 'name' => 'Israeli New Shekel'],
            'INR' => ['symbol' => '₹', 'name' => 'Indian Rupee'],
            'ISK' => ['symbol' => 'kr', 'name' => 'Icelandic Krona'],
            'JOD' => ['symbol' => 'د.ا', 'name' => 'Jordanian Dinar'],
            'JPY' => ['symbol' => '¥', 'name' => 'Japanese Yen'],
            'KES' => ['symbol' => 'KSh', 'name' => 'Kenyan Shilling'],
            'KHR' => ['symbol' => '៛', 'name' => 'Cambodian Riel'],
            'KRW' => ['symbol' => '₩', 'name' => 'South Korean Won'],
            'KWD' => ['symbol' => 'د.ك', 'name' => 'Kuwaiti Dinar'],
            'LKR' => ['symbol' => 'Rs', 'name' => 'Sri Lankan Rupee'],
            'MXN' => ['symbol' => '$', 'name' => 'Mexican Peso'],
            'MYR' => ['symbol' => 'RM', 'name' => 'Malaysian Ringgit'],
            'NGN' => ['symbol' => '₦', 'name' => 'Nigerian Naira'],
            'NOK' => ['symbol' => 'kr', 'name' => 'Norwegian Krone'],
            'NPR' => ['symbol' => 'Rs', 'name' => 'Nepalese Rupee'],
            'NZD' => ['symbol' => '$', 'name' => 'New Zealand Dollar'],
            'OMR' => ['symbol' => 'ر.ع.', 'name' => 'Omani Rial'],
            'PHP' => ['symbol' => '₱', 'name' => 'Philippine Peso'],
            'PKR' => ['symbol' => 'Rs', 'name' => 'Pakistani Rupee'],
            'PLN' => ['symbol' => 'zł', 'name' => 'Polish Zloty'],
            'QAR' => ['symbol' => 'ر.ق', 'name' => 'Qatari Riyal'],
            'RON' => ['symbol' => 'lei', 'name' => 'Romanian Leu'],
            'RUB' => ['symbol' => '₽', 'name' => 'Russian Ruble'],
            'SAR' => ['symbol' => '﷼', 'name' => 'Saudi Riyal'],
            'SEK' => ['symbol' => 'kr', 'name' => 'Swedish Krona'],
            'SGD' => ['symbol' => '$', 'name' => 'Singapore Dollar'],
            'THB' => ['symbol' => '฿', 'name' => 'Thai Baht'],
            'TRY' => ['symbol' => '₺', 'name' => 'Turkish Lira'],
            'TWD' => ['symbol' => '$', 'name' => 'Taiwan Dollar'],
            'UAH' => ['symbol' => '₴', 'name' => 'Ukrainian Hryvnia'],
            'USD' => ['symbol' => '$', 'name' => 'US Dollar'],
            'VND' => ['symbol' => '₫', 'name' => 'Vietnamese Dong'],
            'ZAR' => ['symbol' => 'R', 'name' => 'South African Rand'],
        ];
    @endphp

    <div class="card shell-card mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h1 class="h3 mb-1">Country Management</h1>
            </div>
            <a href="{{ route('admin.countries.create') }}" class="btn btn-primary">Add Country</a>
        </div>
    </div>

    <div class="card shell-card mb-4">
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-5">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Country name, code or currency">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-dark" type="submit">Filter</button>
                    <a href="{{ route('admin.countries.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shell-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Country Name</th>
                        <th>Code</th>
                        <th>Currency</th>
                        <th>Status</th>
                        <th>Cities</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($countries as $country)
                        @php($currencyMeta = $currencyDisplay[$country->currency] ?? null)
                        <tr>
                            <td class="fw-semibold">{{ $country->country_name }}</td>
                            <td>{{ $country->country_code }}</td>
                            <td>
                                <div class="fw-semibold">{{ $country->currency }}{{ $currencyMeta ? ' - '.$currencyMeta['symbol'] : '' }}</div>
                                @if ($currencyMeta)
                                    <div class="small text-secondary">{{ $currencyMeta['name'] }}</div>
                                @endif
                            </td>
                            <td><span class="badge text-bg-{{ $country->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($country->status) }}</span></td>
                            <td>{{ $country->cities_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.countries.edit', $country) }}" class="btn btn-sm btn-outline-primary" aria-label="Edit country" title="Edit country">
                                    <i class="ti ti-pencil"></i>
                                </a>
                                <form action="{{ route('admin.countries.destroy', $country) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this country?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Delete country" title="Delete country">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-5">No countries found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">
            {{ $countries->links() }}
        </div>
    </div>
@endsection
