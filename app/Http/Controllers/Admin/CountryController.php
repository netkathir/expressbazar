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
                });
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
        $data = $request->validate([
            'country_name' => ['required', 'string', 'max:255', 'unique:countries,country_name'],
            'country_code' => ['required', 'string', 'max:10', 'unique:countries,country_code'],
            'currency' => ['required', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

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
        $data = $request->validate([
            'country_name' => ['required', 'string', 'max:255', Rule::unique('countries', 'country_name')->ignore($country->id)],
            'country_code' => ['required', 'string', 'max:10', Rule::unique('countries', 'country_code')->ignore($country->id)],
            'currency' => ['required', 'string', 'max:20'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['updated_by'] = $request->user()?->id;

        $country->update($data);

        return redirect()->route('admin.countries.index')->with('success', 'Country updated successfully.');
    }

    public function destroy(Request $request, Country $country)
    {
        if ($country->cities()->exists()) {
            return back()->withErrors(['delete' => 'Country is mapped with cities and cannot be deleted.']);
        }

        $country->delete();

        return redirect()->route('admin.countries.index')->with('success', 'Country deleted successfully.');
    }
}
