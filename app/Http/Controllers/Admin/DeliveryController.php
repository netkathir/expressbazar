<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\DeliveryConfig;
use App\Models\RegionZone;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeliveryController extends Controller
{
    public function index()
    {
        $configs = DeliveryConfig::query()
            ->with(['country', 'city', 'zone'])
            ->latest()
            ->paginate(10);

        return view('admin.delivery.index', [
            'title' => 'Delivery & Logistics',
            'activeMenu' => 'delivery',
            'configs' => $configs,
        ]);
    }

    public function create()
    {
        return view('admin.delivery.form', [
            'title' => 'Add Delivery Config',
            'activeMenu' => 'delivery',
            'config' => new DeliveryConfig(),
            'countries' => Country::orderBy('country_name')->get(),
            'zones' => RegionZone::with(['country', 'city'])->orderBy('zone_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateConfig($request);
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;
        DeliveryConfig::create($data);

        return redirect()->route('admin.delivery.index')->with('success', 'Delivery configuration created successfully.');
    }

    public function edit(DeliveryConfig $delivery)
    {
        return view('admin.delivery.form', [
            'title' => 'Edit Delivery Config',
            'activeMenu' => 'delivery',
            'config' => $delivery,
            'countries' => Country::orderBy('country_name')->get(),
            'zones' => RegionZone::with(['country', 'city'])->orderBy('zone_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, DeliveryConfig $delivery)
    {
        $data = $this->validateConfig($request, $delivery);
        $data['updated_by'] = $request->user()?->id;
        $delivery->update($data);

        return redirect()->route('admin.delivery.index')->with('success', 'Delivery configuration updated successfully.');
    }

    public function destroy(DeliveryConfig $delivery)
    {
        $delivery->delete();

        return redirect()->route('admin.delivery.index')->with('success', 'Delivery configuration deleted successfully.');
    }

    private function validateConfig(Request $request, ?DeliveryConfig $delivery = null): array
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'zone_id' => [
                'required',
                Rule::unique('delivery_config', 'zone_id')->ignore($delivery?->id),
                'exists:regions_zones,id',
            ],
            'delivery_available' => ['nullable'],
            'delivery_charge' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $data['delivery_available'] = $request->boolean('delivery_available');

        $city = City::findOrFail($data['city_id']);
        if ((int) $city->country_id !== (int) $data['country_id']) {
            throw ValidationException::withMessages(['city_id' => 'Selected city must belong to the selected country.']);
        }

        $zone = RegionZone::findOrFail($data['zone_id']);
        if ((int) $zone->country_id !== (int) $data['country_id'] || (int) $zone->city_id !== (int) $data['city_id']) {
            throw ValidationException::withMessages(['zone_id' => 'Selected zone must belong to the selected city and country.']);
        }

        return $data;
    }
}
