<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $countries = Country::query()
            ->withCount('cities')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $currencyCodes = $this->currencyCodesMatching($search);

                $query->where(function ($subQuery) use ($search, $currencyCodes) {
                    $subQuery->where('country_name', 'like', "%{$search}%")
                        ->orWhere('country_code', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");

                    if ($currencyCodes !== []) {
                        $subQuery->orWhereIn('currency', $currencyCodes);
                    }
                });
                $this->prioritizePrefixSearch($query, ['country_name', 'country_code', 'currency'], $search);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.countries.index', [
            'title' => 'Country Management',
            'activeMenu' => 'countries',
            'countries' => $countries,
        ]);
    }

    public function create()
    {
        return view('admin.countries.form', [
            'title' => 'Add Country',
            'activeMenu' => 'countries',
            'country' => new Country(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCountry($request);

        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        Country::create($data);

        return redirect()->route('admin.countries.index')->with('success', 'Country created successfully.');
    }

    public function edit(Country $country)
    {
        return view('admin.countries.form', [
            'title' => 'Edit Country',
            'activeMenu' => 'countries',
            'country' => $country,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Country $country)
    {
        $data = $this->validateCountry($request, $country);

        $data['updated_by'] = $request->user()?->id;

        $country->update($data);

        return redirect()->route('admin.countries.index')->with('success', 'Country updated successfully.');
    }

    public function destroy(Request $request, Country $country)
    {
        if ($country->cities()->withTrashed()->exists()) {
            return back()->withErrors(['delete' => 'Country is mapped with cities and cannot be deleted.']);
        }

        $this->deleteFromDatabase($country);

        return redirect()->route('admin.countries.index')->with('success', 'Country deleted successfully.');
    }

    private function validateCountry(Request $request, ?Country $country = null): array
    {
        $request->merge([
            'country_name' => trim((string) $request->input('country_name')),
            'country_code' => strtoupper(trim((string) $request->input('country_code'))),
            'currency' => strtoupper(trim((string) $request->input('currency'))),
            'timezone' => trim((string) $request->input('timezone')),
        ]);

        return $request->validate([
            'country_name' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])[A-Za-z .\'()-]+$/', Rule::unique('countries', 'country_name')->ignore($country?->id)],
            'country_code' => ['required', 'string', 'regex:/^[A-Z]{2,3}$/', Rule::unique('countries', 'country_code')->ignore($country?->id)],
            'currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'country_name.regex' => 'Country name may contain only letters, spaces, apostrophes, dots, parentheses, and hyphens.',
            'country_code.regex' => 'Country code must be 2 or 3 letters.',
            'currency.regex' => 'Currency must be a 3-letter code such as INR or GBP.',
        ]);
    }

    private function currencyCodesMatching(string $search): array
    {
        $needle = mb_strtolower(trim($search));

        if ($needle === '') {
            return [];
        }

        $currencies = [
            'AED' => ['د.إ', 'United Arab Emirates Dirham'],
            'AFN' => ['؋', 'Afghan Afghani'],
            'ALL' => ['L', 'Albanian Lek'],
            'AMD' => ['֏', 'Armenian Dram'],
            'AOA' => ['Kz', 'Angolan Kwanza'],
            'ARS' => ['$', 'Argentine Peso'],
            'AUD' => ['$', 'Australian Dollar'],
            'AZN' => ['₼', 'Azerbaijani Manat'],
            'BDT' => ['৳', 'Bangladeshi Taka'],
            'BGN' => ['лв', 'Bulgarian Lev'],
            'BHD' => ['.د.ب', 'Bahraini Dinar'],
            'BRL' => ['R$', 'Brazilian Real'],
            'BSD' => ['$', 'Bahamian Dollar'],
            'BYN' => ['Br', 'Belarusian Ruble'],
            'CAD' => ['$', 'Canadian Dollar'],
            'CHF' => ['Fr', 'Swiss Franc'],
            'CLP' => ['$', 'Chilean Peso'],
            'CNY' => ['¥', 'Chinese Yuan'],
            'COP' => ['$', 'Colombian Peso'],
            'CZK' => ['Kč', 'Czech Koruna'],
            'DKK' => ['kr', 'Danish Krone'],
            'DZD' => ['د.ج', 'Algerian Dinar'],
            'EGP' => ['£', 'Egyptian Pound'],
            'EUR' => ['€', 'Euro'],
            'GBP' => ['£', 'British Pound'],
            'GEL' => ['₾', 'Georgian Lari'],
            'GHS' => ['₵', 'Ghanaian Cedi'],
            'HKD' => ['$', 'Hong Kong Dollar'],
            'HUF' => ['Ft', 'Hungarian Forint'],
            'IDR' => ['Rp', 'Indonesian Rupiah'],
            'ILS' => ['₪', 'Israeli New Shekel'],
            'INR' => ['₹', 'Indian Rupee'],
            'ISK' => ['kr', 'Icelandic Krona'],
            'JOD' => ['د.ا', 'Jordanian Dinar'],
            'JPY' => ['¥', 'Japanese Yen'],
            'KES' => ['KSh', 'Kenyan Shilling'],
            'KHR' => ['៛', 'Cambodian Riel'],
            'KRW' => ['₩', 'South Korean Won'],
            'KWD' => ['د.ك', 'Kuwaiti Dinar'],
            'LKR' => ['Rs', 'Sri Lankan Rupee'],
            'MXN' => ['$', 'Mexican Peso'],
            'MYR' => ['RM', 'Malaysian Ringgit'],
            'NGN' => ['₦', 'Nigerian Naira'],
            'NOK' => ['kr', 'Norwegian Krone'],
            'NPR' => ['Rs', 'Nepalese Rupee'],
            'NZD' => ['$', 'New Zealand Dollar'],
            'OMR' => ['ر.ع.', 'Omani Rial'],
            'PHP' => ['₱', 'Philippine Peso'],
            'PKR' => ['Rs', 'Pakistani Rupee'],
            'PLN' => ['zł', 'Polish Zloty'],
            'QAR' => ['ر.ق', 'Qatari Riyal'],
            'RON' => ['lei', 'Romanian Leu'],
            'RUB' => ['₽', 'Russian Ruble'],
            'SAR' => ['﷼', 'Saudi Riyal'],
            'SEK' => ['kr', 'Swedish Krona'],
            'SGD' => ['$', 'Singapore Dollar'],
            'THB' => ['฿', 'Thai Baht'],
            'TRY' => ['₺', 'Turkish Lira'],
            'TWD' => ['$', 'Taiwan Dollar'],
            'UAH' => ['₴', 'Ukrainian Hryvnia'],
            'USD' => ['$', 'US Dollar'],
            'VND' => ['₫', 'Vietnamese Dong'],
            'ZAR' => ['R', 'South African Rand'],
        ];

        return array_keys(array_filter($currencies, function (array $currency, string $code) use ($needle) {
            $haystack = mb_strtolower($code.' '.$currency[0].' '.$currency[1]);

            return str_contains($haystack, $needle);
        }, ARRAY_FILTER_USE_BOTH));
    }
}
