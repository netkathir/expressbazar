<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Country;
use App\Models\CustomerAddress;
use App\Models\RegionZone;
use Illuminate\Http\Request;

class CustomerAccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer', 403);

        return view('storefront.account.index', [
            'title' => 'My Account',
            'user' => $user,
            'addresses' => $user->addresses()->with(['country', 'city', 'zone'])->latest()->get(),
        ]);
    }

    public function storeAddress(Request $request)
    {
        $user = $request->user();

        abort_if(! $user || $user->role !== 'customer', 403);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'zone_id' => ['nullable', 'exists:regions_zones,id'],
            'postcode' => ['required', 'string', 'max:32'],
            'is_default' => ['nullable'],
        ]);

        $city = City::findOrFail($data['city_id']);
        if ((int) $city->country_id !== (int) $data['country_id']) {
            return back()->withErrors(['city_id' => 'Selected city must belong to the selected country.'])->withInput();
        }

        if (! empty($data['zone_id'])) {
            $zone = RegionZone::query()
                ->where('id', $data['zone_id'])
                ->where('country_id', $data['country_id'])
                ->where('city_id', $data['city_id'])
                ->where('status', 'active')
                ->where('delivery_available', true)
                ->first();

            if (! $zone) {
                return back()->withErrors(['zone_id' => 'Delivery is not available in your area.'])->withInput();
            }
        }

        if ($request->boolean('is_default')) {
            $user->addresses()->update(['is_default' => false]);
        }

        CustomerAddress::create([
            'user_id' => $user->id,
            'label' => $data['label'] ?? null,
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'] ?? null,
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'country_id' => $data['country_id'],
            'city_id' => $data['city_id'],
            'zone_id' => $data['zone_id'] ?? null,
            'postcode' => $data['postcode'],
            'is_default' => $request->boolean('is_default'),
            'status' => 'active',
        ]);

        return redirect()->route('storefront.account')->with('success', 'Address saved successfully.');
    }

    public function destroyAddress(Request $request, CustomerAddress $address)
    {
        abort_if($address->user_id !== $request->user()?->id, 403);

        $address->delete();

        return back()->with('success', 'Address removed.');
    }
}
