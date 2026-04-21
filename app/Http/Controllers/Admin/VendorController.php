<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function index(): View
    {
        $vendors = Vendor::query()
            ->with(['locations'])
            ->withCount('products')
            ->orderBy('name')
            ->paginate(10);

        return view('admin.catalog.vendors.index', [
            'pageTitle' => 'Vendors | Admin | ExpressBazar',
            'vendors' => $vendors,
        ]);
    }

    public function create(): View
    {
        return view('admin.catalog.vendors.create', [
            'pageTitle' => 'Create Vendor | Admin | ExpressBazar',
            'locations' => Location::query()->orderBy('city')->orderBy('pincode')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $locationIds = $data['location_ids'] ?? [];
        unset($data['location_ids']);

        $vendor = Vendor::create($data);
        $vendor->locations()->sync($locationIds);

        return redirect()->route('admin.vendors')->with('status', 'Vendor created successfully.');
    }

    public function edit(Vendor $vendor): View
    {
        return view('admin.catalog.vendors.edit', [
            'pageTitle' => 'Edit Vendor | Admin | ExpressBazar',
            'vendor' => $vendor->load('locations'),
            'locations' => Location::query()->orderBy('city')->orderBy('pincode')->get(),
        ]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $data = $this->validatedData($request, $vendor->id);
        $locationIds = $data['location_ids'] ?? [];
        unset($data['location_ids']);

        $vendor->update($data);
        $vendor->locations()->sync($locationIds);

        return redirect()->route('admin.vendors')->with('status', 'Vendor updated successfully.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $vendor->delete();

        return redirect()->route('admin.vendors')->with('status', 'Vendor deleted successfully.');
    }

    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:vendors,slug' . ($ignoreId ? ',' . $ignoreId : '')],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'service_radius_km' => ['nullable', 'numeric', 'min:0'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'is_active' => ['nullable', 'boolean'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:locations,id'],
        ]);
    }
}
