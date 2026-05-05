@extends('layouts.admin')

@push('head')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        .select2-container--default .select2-selection--single {
            height: 56px;
            border: 1px solid #d8dee9;
            border-radius: 10px;
            display: flex;
            align-items: center;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #111827;
            line-height: 56px;
            padding-left: 18px;
            padding-right: 40px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 54px;
            right: 12px;
        }
    </style>
@endpush

@section('content')
    <div class="card shell-card">
        <div class="card-body p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="h3 mb-1">{{ $mode === 'create' ? 'Add Country' : 'Edit Country' }}</h1>
                </div>
                <a href="{{ route('admin.countries.index') }}" class="btn btn-outline-secondary" data-dirty-back>Back</a>
            </div>

            <form method="POST" action="{{ $mode === 'create' ? route('admin.countries.store') : route('admin.countries.update', $country) }}" class="row g-3" data-dirty-check>
                @csrf
                @if ($mode === 'edit')
                    @method('PUT')
                @endif

                <div class="col-md-6">
                    <label class="form-label">Country Name</label>
                    <select name="country_name" id="country_name" class="form-control" required>
                        <option value="">Select Country</option>
                    </select>
                </div>
                <div class="col-md-3 d-none">
                    <label class="form-label">Country Code</label>
                    <select name="country_code" id="country_code" class="form-control text-uppercase" required>
                        <option value="">Select Code</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Currency</label>
                    <select name="currency" id="currency" class="form-control text-uppercase" required>
                        <option value="">Select Currency</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" value="{{ old('timezone', $country->timezone) }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $country->status ?: 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $country->status) === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">{{ $mode === 'create' ? 'Save Country' : 'Update Country' }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
    <script>
        (function ($) {
            const countries = [
                { name: 'Afghanistan', code: 'AF' },
                { name: 'Albania', code: 'AL' },
                { name: 'Algeria', code: 'DZ' },
                { name: 'Andorra', code: 'AD' },
                { name: 'Angola', code: 'AO' },
                { name: 'Argentina', code: 'AR' },
                { name: 'Armenia', code: 'AM' },
                { name: 'Australia', code: 'AU' },
                { name: 'Austria', code: 'AT' },
                { name: 'Azerbaijan', code: 'AZ' },
                { name: 'Bahamas', code: 'BS' },
                { name: 'Bahrain', code: 'BH' },
                { name: 'Bangladesh', code: 'BD' },
                { name: 'Belarus', code: 'BY' },
                { name: 'Belgium', code: 'BE' },
                { name: 'Brazil', code: 'BR' },
                { name: 'Bulgaria', code: 'BG' },
                { name: 'Cambodia', code: 'KH' },
                { name: 'Canada', code: 'CA' },
                { name: 'Chile', code: 'CL' },
                { name: 'China', code: 'CN' },
                { name: 'Colombia', code: 'CO' },
                { name: 'Croatia', code: 'HR' },
                { name: 'Cyprus', code: 'CY' },
                { name: 'Czech Republic', code: 'CZ' },
                { name: 'Denmark', code: 'DK' },
                { name: 'Egypt', code: 'EG' },
                { name: 'Estonia', code: 'EE' },
                { name: 'Finland', code: 'FI' },
                { name: 'France', code: 'FR' },
                { name: 'Georgia', code: 'GE' },
                { name: 'Germany', code: 'DE' },
                { name: 'Ghana', code: 'GH' },
                { name: 'Greece', code: 'GR' },
                { name: 'Hong Kong', code: 'HK' },
                { name: 'Hungary', code: 'HU' },
                { name: 'Iceland', code: 'IS' },
                { name: 'India', code: 'IN' },
                { name: 'Indonesia', code: 'ID' },
                { name: 'Ireland', code: 'IE' },
                { name: 'Israel', code: 'IL' },
                { name: 'Italy', code: 'IT' },
                { name: 'Japan', code: 'JP' },
                { name: 'Jordan', code: 'JO' },
                { name: 'Kenya', code: 'KE' },
                { name: 'Kuwait', code: 'KW' },
                { name: 'Malaysia', code: 'MY' },
                { name: 'Mexico', code: 'MX' },
                { name: 'Nepal', code: 'NP' },
                { name: 'Netherlands', code: 'NL' },
                { name: 'New Zealand', code: 'NZ' },
                { name: 'Nigeria', code: 'NG' },
                { name: 'Norway', code: 'NO' },
                { name: 'Oman', code: 'OM' },
                { name: 'Pakistan', code: 'PK' },
                { name: 'Philippines', code: 'PH' },
                { name: 'Poland', code: 'PL' },
                { name: 'Portugal', code: 'PT' },
                { name: 'Qatar', code: 'QA' },
                { name: 'Romania', code: 'RO' },
                { name: 'Russia', code: 'RU' },
                { name: 'Saudi Arabia', code: 'SA' },
                { name: 'Singapore', code: 'SG' },
                { name: 'South Africa', code: 'ZA' },
                { name: 'South Korea', code: 'KR' },
                { name: 'Spain', code: 'ES' },
                { name: 'Sri Lanka', code: 'LK' },
                { name: 'Sweden', code: 'SE' },
                { name: 'Switzerland', code: 'CH' },
                { name: 'Taiwan', code: 'TW' },
                { name: 'Thailand', code: 'TH' },
                { name: 'Turkey', code: 'TR' },
                { name: 'Ukraine', code: 'UA' },
                { name: 'United Arab Emirates', code: 'AE' },
                { name: 'United Kingdom', code: 'GB' },
                { name: 'United States', code: 'US' },
                { name: 'Vietnam', code: 'VN' },
            ];

            const countryCurrencies = {
                Afghanistan: 'AFN',
                Albania: 'ALL',
                Algeria: 'DZD',
                Andorra: 'EUR',
                Angola: 'AOA',
                Argentina: 'ARS',
                Armenia: 'AMD',
                Australia: 'AUD',
                Austria: 'EUR',
                Azerbaijan: 'AZN',
                Bahamas: 'BSD',
                Bahrain: 'BHD',
                Bangladesh: 'BDT',
                Belarus: 'BYN',
                Belgium: 'EUR',
                Brazil: 'BRL',
                Bulgaria: 'BGN',
                Cambodia: 'KHR',
                Canada: 'CAD',
                Chile: 'CLP',
                China: 'CNY',
                Colombia: 'COP',
                Croatia: 'EUR',
                Cyprus: 'EUR',
                'Czech Republic': 'CZK',
                Denmark: 'DKK',
                Egypt: 'EGP',
                Estonia: 'EUR',
                Finland: 'EUR',
                France: 'EUR',
                Georgia: 'GEL',
                Germany: 'EUR',
                Ghana: 'GHS',
                Greece: 'EUR',
                'Hong Kong': 'HKD',
                Hungary: 'HUF',
                Iceland: 'ISK',
                India: 'INR',
                Indonesia: 'IDR',
                Ireland: 'EUR',
                Israel: 'ILS',
                Italy: 'EUR',
                Japan: 'JPY',
                Jordan: 'JOD',
                Kenya: 'KES',
                Kuwait: 'KWD',
                Malaysia: 'MYR',
                Mexico: 'MXN',
                Nepal: 'NPR',
                Netherlands: 'EUR',
                'New Zealand': 'NZD',
                Nigeria: 'NGN',
                Norway: 'NOK',
                Oman: 'OMR',
                Pakistan: 'PKR',
                Philippines: 'PHP',
                Poland: 'PLN',
                Portugal: 'EUR',
                Qatar: 'QAR',
                Romania: 'RON',
                Russia: 'RUB',
                'Saudi Arabia': 'SAR',
                Singapore: 'SGD',
                'South Africa': 'ZAR',
                'South Korea': 'KRW',
                Spain: 'EUR',
                'Sri Lanka': 'LKR',
                Sweden: 'SEK',
                Switzerland: 'CHF',
                Taiwan: 'TWD',
                Thailand: 'THB',
                Turkey: 'TRY',
                Ukraine: 'UAH',
                'United Arab Emirates': 'AED',
                'United Kingdom': 'GBP',
                'United States': 'USD',
                Vietnam: 'VND',
            };

            const currencies = [
                { code: 'AED', name: 'United Arab Emirates Dirham' },
                { code: 'AFN', name: 'Afghan Afghani' },
                { code: 'ALL', name: 'Albanian Lek' },
                { code: 'AMD', name: 'Armenian Dram' },
                { code: 'AOA', name: 'Angolan Kwanza' },
                { code: 'ARS', name: 'Argentine Peso' },
                { code: 'AUD', name: 'Australian Dollar' },
                { code: 'AZN', name: 'Azerbaijani Manat' },
                { code: 'BDT', name: 'Bangladeshi Taka' },
                { code: 'BGN', name: 'Bulgarian Lev' },
                { code: 'BHD', name: 'Bahraini Dinar' },
                { code: 'BRL', name: 'Brazilian Real' },
                { code: 'BSD', name: 'Bahamian Dollar' },
                { code: 'BYN', name: 'Belarusian Ruble' },
                { code: 'CAD', name: 'Canadian Dollar' },
                { code: 'CHF', name: 'Swiss Franc' },
                { code: 'CLP', name: 'Chilean Peso' },
                { code: 'CNY', name: 'Chinese Yuan' },
                { code: 'COP', name: 'Colombian Peso' },
                { code: 'CZK', name: 'Czech Koruna' },
                { code: 'DKK', name: 'Danish Krone' },
                { code: 'DZD', name: 'Algerian Dinar' },
                { code: 'EGP', name: 'Egyptian Pound' },
                { code: 'EUR', name: 'Euro' },
                { code: 'GBP', name: 'British Pound' },
                { code: 'GEL', name: 'Georgian Lari' },
                { code: 'GHS', name: 'Ghanaian Cedi' },
                { code: 'HKD', name: 'Hong Kong Dollar' },
                { code: 'HUF', name: 'Hungarian Forint' },
                { code: 'IDR', name: 'Indonesian Rupiah' },
                { code: 'ILS', name: 'Israeli New Shekel' },
                { code: 'INR', name: 'Indian Rupee' },
                { code: 'ISK', name: 'Icelandic Krona' },
                { code: 'JOD', name: 'Jordanian Dinar' },
                { code: 'JPY', name: 'Japanese Yen' },
                { code: 'KES', name: 'Kenyan Shilling' },
                { code: 'KHR', name: 'Cambodian Riel' },
                { code: 'KRW', name: 'South Korean Won' },
                { code: 'KWD', name: 'Kuwaiti Dinar' },
                { code: 'LKR', name: 'Sri Lankan Rupee' },
                { code: 'MXN', name: 'Mexican Peso' },
                { code: 'MYR', name: 'Malaysian Ringgit' },
                { code: 'NGN', name: 'Nigerian Naira' },
                { code: 'NOK', name: 'Norwegian Krone' },
                { code: 'NPR', name: 'Nepalese Rupee' },
                { code: 'NZD', name: 'New Zealand Dollar' },
                { code: 'OMR', name: 'Omani Rial' },
                { code: 'PHP', name: 'Philippine Peso' },
                { code: 'PKR', name: 'Pakistani Rupee' },
                { code: 'PLN', name: 'Polish Zloty' },
                { code: 'QAR', name: 'Qatari Riyal' },
                { code: 'RON', name: 'Romanian Leu' },
                { code: 'RUB', name: 'Russian Ruble' },
                { code: 'SAR', name: 'Saudi Riyal' },
                { code: 'SEK', name: 'Swedish Krona' },
                { code: 'SGD', name: 'Singapore Dollar' },
                { code: 'THB', name: 'Thai Baht' },
                { code: 'TRY', name: 'Turkish Lira' },
                { code: 'TWD', name: 'Taiwan Dollar' },
                { code: 'UAH', name: 'Ukrainian Hryvnia' },
                { code: 'USD', name: 'US Dollar' },
                { code: 'VND', name: 'Vietnamese Dong' },
                { code: 'ZAR', name: 'South African Rand' },
            ];

            const existingName = @json(old('country_name', $country->country_name));
            const existingCode = @json(old('country_code', $country->country_code));
            const existingCurrency = @json(old('currency', $country->currency));
            const $countryName = $('#country_name');
            const $countryCode = $('#country_code');
            const $currency = $('#currency');

            function appendCountry(country) {
                if (country.name && !$countryName.find(`option[value="${escapeSelector(country.name)}"]`).length) {
                    $countryName.append(new Option(country.name, country.name, false, false));
                    $countryName.find(`option[value="${escapeSelector(country.name)}"]`)
                        .attr('data-code', country.code || '')
                        .attr('data-currency', country.currency || countryCurrencies[country.name] || '');
                }

                if (country.code && !$countryCode.find(`option[value="${escapeSelector(country.code)}"]`).length) {
                    $countryCode.append(new Option(country.code, country.code, false, false));
                    $countryCode.find(`option[value="${escapeSelector(country.code)}"]`).attr('data-name', country.name || '');
                }
            }

            function appendCurrency(currency) {
                if (!currency.code || $currency.find(`option[value="${escapeSelector(currency.code)}"]`).length) {
                    return;
                }

                $currency.append(new Option(`${currency.code} - ${currency.name}`, currency.code, false, false));
            }

            function escapeSelector(value) {
                return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            }

            countries.forEach(appendCountry);
            currencies.forEach(appendCurrency);

            if (existingName || existingCode) {
                appendCountry({ name: existingName, code: existingCode, currency: existingCurrency });
            }

            if (existingCurrency) {
                appendCurrency({ code: existingCurrency, name: existingCurrency });
            }

            $countryName.select2({
                placeholder: 'Select Country',
                width: '100%',
            });

            $countryCode.select2({
                placeholder: 'Select Code',
                width: '100%',
            });

            $currency.select2({
                placeholder: 'Select Currency',
                width: '100%',
            });

            $countryName.on('change', function () {
                const code = $(this).find(':selected').data('code');
                const currency = $(this).find(':selected').data('currency');

                if (code && $countryCode.val() !== String(code)) {
                    $countryCode.val(code).trigger('change.select2');
                }

                if (currency && $currency.val() !== String(currency)) {
                    $currency.val(currency).trigger('change.select2');
                }
            });

            $countryCode.on('change', function () {
                const name = $(this).find(':selected').data('name');

                if (name && $countryName.val() !== String(name)) {
                    $countryName.val(name).trigger('change.select2');
                }
            });

            if (existingName) {
                $countryName.val(existingName).trigger('change');
            }

            if (existingCode) {
                $countryCode.val(existingCode).trigger('change');
            }

            if (existingCurrency) {
                $currency.val(existingCurrency).trigger('change');
            }
        })(jQuery);
    </script>
@endpush
