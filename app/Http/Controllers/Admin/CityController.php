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
                });
                $this->prioritizePrefixSearch($query, ['city_name', 'city_code'], $search);
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
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'state' => ['nullable', 'string', 'max:255'],
            'city_name' => ['required', 'string', 'max:255'],
            'city_code' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

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
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'state' => ['nullable', 'string', 'max:255'],
            'city_name' => ['required', 'string', 'max:255'],
            'city_code' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

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
}
