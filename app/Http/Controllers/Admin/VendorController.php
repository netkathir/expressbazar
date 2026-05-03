<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VendorCredentialsMail;
use App\Mail\VendorSetupMail;
use App\Models\City;
use App\Models\Country;
use App\Models\Product;
use App\Models\RegionZone;
use App\Models\Role;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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
            'roles' => Role::query()->where('status', 'active')->orderBy('role_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateVendor($request);

        $plainPassword = $this->generatePassword();
        $data['password'] = Hash::make($plainPassword);
        $data['setup_token'] = Str::random(40);
        $data['is_setup_complete'] = false;
        $data['created_by'] = $request->user()?->id;
        $data['updated_by'] = $request->user()?->id;

        $vendor = Vendor::create($data);
        $mailSent = $this->sendCredentialsMail($vendor, $plainPassword);
        $setupMailSent = $this->sendSetupMail($vendor);

        return redirect()
            ->route('admin.vendors.index')
            ->with('success', $this->vendorMailMessage('Vendor created successfully.', $vendor, $mailSent, $setupMailSent));
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
            'roles' => Role::query()->where('status', 'active')->orderBy('role_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $this->validateVendor($request, $vendor);
        $data['updated_by'] = $request->user()?->id;
        $plainPassword = null;

        if ($request->boolean('send_credentials') || empty($vendor->password)) {
            $plainPassword = $this->generatePassword();
            $data['password'] = Hash::make($plainPassword);
            $data['setup_token'] = Str::random(40);
            $data['is_setup_complete'] = false;
        }

        $vendor->update($data);

        $mailSent = null;
        $setupMailSent = null;
        if ($plainPassword) {
            $freshVendor = $vendor->fresh();
            $mailSent = $this->sendCredentialsMail($freshVendor, $plainPassword);
            $setupMailSent = $this->sendSetupMail($freshVendor);
        }

        return redirect()
            ->route('admin.vendors.index')
            ->with('success', $this->vendorMailMessage('Vendor updated successfully.', $vendor->fresh(), $mailSent, $setupMailSent));
    }

    public function destroy(Vendor $vendor)
    {
        if (Product::withTrashed()->where('vendor_id', $vendor->id)->exists()) {
            return back()->withErrors(['delete' => 'Vendor is mapped with products and cannot be deleted.']);
        }

        $this->deleteFromDatabase($vendor);

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
        $request->merge([
            'vendor_name' => trim((string) $request->input('vendor_name')),
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'phone' => preg_replace('/\s+/', '', (string) $request->input('phone')),
            'pincode' => trim((string) $request->input('pincode')),
        ]);

        $data = $request->validate([
            'vendor_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9\s&.,\'()\-\/]+$/',
            ],
            'email' => ['required', 'email', 'max:255', Rule::unique('vendors', 'email')->ignore($vendor?->id)],
            'role' => ['required', 'exists:roles,role_name'],
            'phone' => ['nullable', 'regex:/^[0-9]{10,15}$/'],
            'address' => ['nullable', 'string'],
            'pincode' => ['nullable', 'regex:/^[0-9]{6}$/'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'region_zone_id' => ['required', 'exists:regions_zones,id'],
            'inventory_mode' => ['required', Rule::in(['internal', 'epos'])],
            'api_url' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'credentials' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'vendor_name.regex' => 'Vendor name must include letters or numbers and cannot contain unsupported special characters.',
            'phone.regex' => 'Phone must contain only 10 to 15 digits.',
            'pincode.regex' => 'Pincode must be exactly 6 digits.',
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

        $data['pincode'] = mb_strtoupper((string) ($data['pincode'] ?? '')) ?: null;
        $data['phone'] = (string) ($data['phone'] ?? '') ?: null;
        $data['zone_id'] = $data['region_zone_id'];

        return $data;
    }

    private function generatePassword(): string
    {
        return Str::password(12, true, true, false);
    }

    private function sendCredentialsMail(Vendor $vendor, string $plainPassword): bool
    {
        try {
            $mail = new VendorCredentialsMail($vendor, $plainPassword);

            Mail::to($vendor->email)->send($mail);

            Log::info('Vendor credentials email sent.', [
                'vendor_id' => $vendor->id,
                'email' => $vendor->email,
                'mailer' => config('mail.default'),
                'from' => $mail->from[0]['address'] ?? config('mail.from.address'),
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Vendor credentials email failed.', [
                'vendor_id' => $vendor->id,
                'email' => $vendor->email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function sendSetupMail(Vendor $vendor): bool
    {
        if (! $vendor->setup_token) {
            return false;
        }

        try {
            Mail::to($vendor->email)->send(new VendorSetupMail($vendor, route('vendor.setup.edit', $vendor->setup_token)));

            Log::info('Vendor setup email sent.', [
                'vendor_id' => $vendor->id,
                'email' => $vendor->email,
                'mailer' => config('mail.default'),
            ]);

            return true;
        } catch (\Throwable $exception) {
            Log::error('Vendor setup email failed.', [
                'vendor_id' => $vendor->id,
                'email' => $vendor->email,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function vendorMailMessage(string $prefix, Vendor $vendor, ?bool $credentialsSent, ?bool $setupSent): string
    {
        $message = $prefix;

        if ($credentialsSent === true) {
            $message .= " Credentials email sent to {$vendor->email}.";
        } elseif ($credentialsSent === false) {
            $message .= ' Credentials email could not be sent.';
        }

        if ($setupSent === true) {
            $message .= " Setup email sent to {$vendor->email}.";
        } elseif ($setupSent === false) {
            $message .= ' Setup email could not be sent.';
        }

        return $message;
    }
}
