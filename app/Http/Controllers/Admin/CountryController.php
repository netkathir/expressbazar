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
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('country_name', 'like', "%{$search}%")
                        ->orWhere('country_code', 'like', "%{$search}%")
                        ->orWhere('currency', 'like', "%{$search}%");
<<<<<<< HEAD
                })
                    ->orderByRaw('CASE WHEN country_name LIKE ? OR country_code LIKE ? OR currency LIKE ? THEN 0 ELSE 1 END', [$search.'%', $search.'%', $search.'%'])
                    ->orderBy('country_name');
=======
                });
                $this->prioritizePrefixSearch($query, ['country_name', 'country_code', 'currency'], $search);
>>>>>>> b613057478c82536e6c638344512541362616b16
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
<<<<<<< HEAD
        $data = $this->validateCountry($request);
=======
        $data = $request->validate([
            'country_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z\s]+$/',
                'unique:countries,country_name',
            ],
            'country_code' => ['required', 'string', 'max:10', 'unique:countries,country_code'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'country_name.regex' => 'Country name may contain letters and spaces only.',
            'currency.size' => 'Currency must be exactly 3 characters.',
            'currency.regex' => 'Currency must use a valid ISO 4217 code with 3 uppercase letters.',
        ]);
>>>>>>> b613057478c82536e6c638344512541362616b16

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
}
