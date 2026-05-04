<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\DeliveryConfig;
use App\Models\RegionZone;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegionZoneController extends Controller
{
    public function index(Request $request)
    {
        $zones = RegionZone::query()
            ->with(['country', 'city'])
            ->when($request->filled('country_id'), fn ($query) => $query->where('country_id', $request->integer('country_id')))
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->integer('city_id')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('zone_name', 'like', "%{$search}%")
                        ->orWhere('zone_code', 'like', "%{$search}%");
<<<<<<< HEAD
                })
                    ->orderByRaw('CASE WHEN zone_name LIKE ? OR zone_code LIKE ? THEN 0 ELSE 1 END', [$search.'%', $search.'%'])
                    ->orderBy('zone_name');
=======
                });
                $this->prioritizePrefixSearch($query, ['zone_name', 'zone_code'], $search);
>>>>>>> b613057478c82536e6c638344512541362616b16
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.zones.index', [
            'title' => 'Region / Zone Management',
            'activeMenu' => 'zones',
            'zones' => $zones,
            'countries' => Country::orderBy('country_name')->get(),
            'cities' => City::orderBy('city_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.zones.form', [
            'title' => 'Add Region / Zone',
            'activeMenu' => 'zones',
            'zone' => new RegionZone(),
            'countries' => Country::orderBy('country_name')->get(),
            'cities' => City::orderBy('city_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateZone($request);

        $duplicate = RegionZone::where('city_id', $data['city_id'])
            ->whereRaw('LOWER(zone_name) = ?', [strtolower($data['zone_name'])])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['zone_name' => 'Duplicate region under the same city is not allowed.'])->withInput();
        }

        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        RegionZone::create($data);

        return redirect()->route('admin.zones.index')->with('success', 'Region / zone created successfully.');
    }

    public function edit(RegionZone $zone)
    {
        return view('admin.zones.form', [
            'title' => 'Edit Region / Zone',
            'activeMenu' => 'zones',
            'zone' => $zone,
            'countries' => Country::orderBy('country_name')->get(),
            'cities' => City::orderBy('city_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, RegionZone $zone)
    {
        $data = $this->validateZone($request);

        $duplicate = RegionZone::where('city_id', $data['city_id'])
            ->whereRaw('LOWER(zone_name) = ?', [strtolower($data['zone_name'])])
            ->where('id', '!=', $zone->id)
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['zone_name' => 'Duplicate region under the same city is not allowed.'])->withInput();
        }

        $data['updated_by'] = $request->user()?->id;

        $zone->update($data);

        return redirect()->route('admin.zones.index')->with('success', 'Region / zone updated successfully.');
    }

    public function destroy(RegionZone $zone)
    {
        if (Vendor::withTrashed()->where('region_zone_id', $zone->id)->exists() || DeliveryConfig::where('zone_id', $zone->id)->exists()) {
            return back()->withErrors(['delete' => 'Region / zone is mapped with vendors/delivery settings and cannot be deleted.']);
        }

        $this->deleteFromDatabase($zone);

        return redirect()->route('admin.zones.index')->with('success', 'Region / zone deleted successfully.');
    }

    private function validateZone(Request $request): array
    {
        $request->merge([
            'zone_name' => trim((string) $request->input('zone_name')),
            'zone_code' => strtoupper(trim((string) $request->input('zone_code'))),
        ]);

        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'zone_name' => ['required', 'string', 'max:255', 'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9 .\'()\/-]+$/'],
            'zone_code' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/'],
            'delivery_available' => ['required', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'zone_name.regex' => 'Zone name must include letters or numbers and cannot contain unsupported special characters.',
            'zone_code.regex' => 'Zone code may contain only letters, numbers, and hyphens.',
        ]);

        $cityMatchesCountry = City::query()
            ->whereKey($data['city_id'])
            ->where('country_id', $data['country_id'])
            ->exists();

        if (! $cityMatchesCountry) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'city_id' => 'Selected city must belong to the selected country.',
            ]);
        }

        return $data;
    }
}
