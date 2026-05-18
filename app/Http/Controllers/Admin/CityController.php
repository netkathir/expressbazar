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
            ->when($request->filled('state'), fn ($query) => $query->where('state', $request->string('state')))
            ->when($request->filled('city_id'), fn ($query) => $query->whereKey($request->integer('city_id')))
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
            'stateOptions' => $this->stateOptions(),
            'cityOptions' => $this->cityOptions(),
        ]);
    }

    public function create()
    {
        return view('admin.cities.form', [
            'title' => 'Add City',
            'activeMenu' => 'cities',
            'city' => new City(),
            'countries' => Country::orderBy('country_name')->get(),
            'stateOptions' => $this->stateOptions(),
            'cityOptions' => $this->cityOptions(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCity($request);

        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $exists = $this->cityDuplicateQuery($data)
            ->exists();

        if ($exists) {
            return back()->withErrors(['city_name' => 'City must be unique within the same country and state.'])->withInput();
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
            'stateOptions' => $this->stateOptions(),
            'cityOptions' => $this->cityOptions(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, City $city)
    {
        $data = $this->validateCity($request);

        $duplicate = $this->cityDuplicateQuery($data)
            ->where('id', '!=', $city->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['city_name' => 'City must be unique within the same country and state.'])->withInput();
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

    private function cityDuplicateQuery(array $data)
    {
        $state = (string) ($data['state'] ?? '');

        return City::query()
            ->where('country_id', $data['country_id'])
            ->when(
                $state !== '',
                fn ($query) => $query->whereRaw('LOWER(state) = ?', [mb_strtolower($state)]),
                fn ($query) => $query->where(function ($stateQuery) {
                    $stateQuery->whereNull('state')->orWhere('state', '');
                })
            )
            ->whereRaw('LOWER(city_name) = ?', [mb_strtolower($data['city_name'])]);
    }

    private function stateOptions(): array
    {
        return City::query()
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->orderBy('state')
            ->get(['country_id', 'state'])
            ->map(fn (City $city) => [
                'country_id' => (string) $city->country_id,
                'state' => $city->state,
            ])
            ->unique(fn (array $state) => $state['country_id'].'|'.mb_strtolower($state['state']))
            ->values()
            ->all();
    }

    private function cityOptions(): array
    {
        return City::query()
            ->orderBy('city_name')
            ->get(['id', 'country_id', 'state', 'city_name'])
            ->map(fn (City $city) => [
                'id' => (string) $city->id,
                'country_id' => (string) $city->country_id,
                'state' => (string) $city->state,
                'city_name' => $city->city_name,
            ])
            ->values()
            ->all();
    }
}
