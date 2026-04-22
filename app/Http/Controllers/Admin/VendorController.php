<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\RegionZone;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $vendors = Vendor::query()
            ->with(['country', 'city', 'zone'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('vendor_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('inventory_mode'), fn ($query) => $query->where('inventory_mode', $request->string('inventory_mode')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.vendors.index', [
            'title' => 'Vendor Master',
            'activeMenu' => 'vendors',
            'vendors' => $vendors,
        ]);
    }

    public function create()
    {
        return view('admin.vendors.form', [
            'title' => 'Add Vendor',
            'activeMenu' => 'vendors',
            'vendor' => new Vendor(),
            'countries' => Country::orderBy('country_name')->get(),
            'cities' => City::orderBy('city_name')->get(),
            'zones' => RegionZone::orderBy('zone_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateVendor($request);

        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        Vendor::create($data);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.vendors.form', [
            'title' => 'Edit Vendor',
            'activeMenu' => 'vendors',
            'vendor' => $vendor,
            'countries' => Country::orderBy('country_name')->get(),
            'cities' => City::orderBy('city_name')->get(),
            'zones' => RegionZone::orderBy('zone_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $this->validateVendor($request, $vendor);
        $data['updated_by'] = $request->user()?->id;

        $vendor->update($data);

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor)
    {
        $vendor->delete();

        return redirect()->route('admin.vendors.index')->with('success', 'Vendor deleted successfully.');
    }

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
        ]);

        $cities = City::query()
            ->where('country_id', $data['country_id'])
            ->where('status', 'active')
            ->orderBy('city_name')
            ->get(['id', 'city_name']);

        return response()->json([
            'data' => $cities,
        ]);
    }

    public function zones(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
        ]);

        $zones = RegionZone::query()
            ->where('country_id', $data['country_id'])
            ->where('city_id', $data['city_id'])
            ->where('status', 'active')
            ->orderBy('zone_name')
            ->get(['id', 'zone_name']);

        return response()->json([
            'data' => $zones,
        ]);
    }

    private function validateVendor(Request $request, ?Vendor $vendor = null): array
    {
        $data = $request->validate([
            'vendor_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('vendors', 'email')->ignore($vendor?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'region_zone_id' => ['required', 'exists:regions_zones,id'],
            'inventory_mode' => ['required', Rule::in(['internal', 'epos'])],
            'api_url' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'credentials' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $city = City::findOrFail($data['city_id']);
        if ((int) $city->country_id !== (int) $data['country_id']) {
            throw ValidationException::withMessages([
                'city_id' => 'Selected city must belong to the selected country.',
            ]);
        }

        $zone = RegionZone::findOrFail($data['region_zone_id']);
        if ((int) $zone->country_id !== (int) $data['country_id'] || (int) $zone->city_id !== (int) $data['city_id']) {
            throw ValidationException::withMessages([
                'region_zone_id' => 'Selected zone must belong to the selected city and country.',
            ]);
        }

        return $data;
    }
}
