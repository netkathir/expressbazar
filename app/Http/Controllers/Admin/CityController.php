<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\CustomerAddress;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $cities = City::query()
            ->with('country')
            ->withCount('zones')
            ->when($request->filled('country_id'), fn ($query) => $query->where('country_id', $request->integer('country_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('city_name', 'like', "%{$search}%")
                        ->orWhere('city_code', 'like', "%{$search}%");
                })
                    ->orderByRaw('CASE WHEN city_name LIKE ? OR city_code LIKE ? THEN 0 ELSE 1 END', [$search.'%', $search.'%'])
                    ->orderBy('city_name');
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.cities.index', [
            'title' => 'City Management',
            'activeMenu' => 'cities',
            'cities' => $cities,
            'countries' => Country::orderBy('country_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.cities.form', [
            'title' => 'Add City',
            'activeMenu' => 'cities',
            'city' => new City(),
            'countries' => Country::orderBy('country_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCity($request);

        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $exists = City::where('country_id', $data['country_id'])
            ->whereRaw('LOWER(city_name) = ?', [strtolower($data['city_name'])])
            ->exists();

        if ($exists) {
            return back()->withErrors(['city_name' => 'City must be unique within the same country.'])->withInput();
        }

        City::create($data);

        return redirect()->route('admin.cities.index')->with('success', 'City created successfully.');
    }

    public function edit(City $city)
    {
        return view('admin.cities.form', [
            'title' => 'Edit City',
            'activeMenu' => 'cities',
            'city' => $city,
            'countries' => Country::orderBy('country_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, City $city)
    {
        $data = $this->validateCity($request);

        $duplicate = City::where('country_id', $data['country_id'])
            ->whereRaw('LOWER(city_name) = ?', [strtolower($data['city_name'])])
            ->where('id', '!=', $city->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['city_name' => 'City must be unique within the same country.'])->withInput();
        }

        $data['updated_by'] = $request->user()?->id;

        $city->update($data);

        return redirect()->route('admin.cities.index')->with('success', 'City updated successfully.');
    }

    public function destroy(City $city)
    {
        if ($city->zones()->withTrashed()->exists() || CustomerAddress::where('city_id', $city->id)->exists()) {
            return back()->withErrors(['delete' => 'City is mapped with regions/vendors/customer addresses and cannot be deleted.']);
        }

        $this->deleteFromDatabase($city);

        return redirect()->route('admin.cities.index')->with('success', 'City deleted successfully.');
    }

    private function validateCity(Request $request): array
    {
        $request->merge([
            'state' => trim((string) $request->input('state')),
            'city_name' => trim((string) $request->input('city_name')),
            'city_code' => strtoupper(trim((string) $request->input('city_code'))),
        ]);

        return $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'state' => ['nullable', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])[A-Za-z .\'()-]+$/'],
            'city_name' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z])[A-Za-z .\'()-]+$/'],
            'city_code' => ['nullable', 'string', 'max:10', 'regex:/^[A-Z0-9-]+$/'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'state.regex' => 'State may contain only letters, spaces, apostrophes, dots, parentheses, and hyphens.',
            'city_name.regex' => 'City name may contain only letters, spaces, apostrophes, dots, parentheses, and hyphens.',
            'city_code.regex' => 'City code may contain only letters, numbers, and hyphens.',
        ]);
    }
}
