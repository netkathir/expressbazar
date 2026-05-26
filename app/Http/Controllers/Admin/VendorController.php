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
                $this->prioritizePrefixSearch($query, ['vendor_name', 'email', 'phone'], $search);
            })
            ->when($request->filled('country_id'), fn ($query) => $query->where('country_id', $request->integer('country_id')))
            ->when($request->filled('state'), fn ($query) => $query->whereHas('city', fn ($cityQuery) => $cityQuery->where('state', $request->string('state'))))
            ->when($request->filled('city_id'), fn ($query) => $query->where('city_id', $request->integer('city_id')))
            ->when($request->filled('inventory_mode'), fn ($query) => $query->where('inventory_mode', $request->string('inventory_mode')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.vendors.index', [
            'title' => 'Vendor Master',
            'activeMenu' => 'vendors',
            'vendors' => $vendors,
            'countries' => Country::orderBy('country_name')->get(),
            'stateOptions' => $this->stateOptions(false),
            'cityOptions' => $this->cityOptions(false),
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

        return $this->redirectToIndex($request, 'admin.vendors.index', $this->vendorMailMessage('Vendor created successfully.', $vendor, $mailSent, $setupMailSent));
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
        $originalEmail = $vendor->email;
        $data = $this->validateVendor($request, $vendor);
        $data['updated_by'] = $request->user()?->id;
        $plainPassword = null;

        if (empty($vendor->password) || $originalEmail !== $data['email']) {
            $plainPassword = $this->generatePassword();
            $data['password'] = Hash::make($plainPassword);
            $data['setup_token'] = Str::random(40);
            $data['is_setup_complete'] = false;
        } elseif (empty($vendor->setup_token)) {
            $data['setup_token'] = Str::random(40);
        }

        $vendor->update($data);

        $mailSent = null;
        $setupMailSent = null;
        $freshVendor = $vendor->fresh();

        $mailSent = $this->sendCredentialsMail($freshVendor, $plainPassword);
        $setupMailSent = $this->sendSetupMail($freshVendor);

        return $this->redirectToIndex($request, 'admin.vendors.index', $this->vendorMailMessage('Vendor updated successfully.', $freshVendor, $mailSent, $setupMailSent));
    }

    public function destroy(Request $request, Vendor $vendor)
    {
        if (Product::withTrashed()->where('vendor_id', $vendor->id)->exists()) {
            return back()->withErrors(['delete' => 'Vendor is mapped with products and cannot be deleted.']);
        }

        $this->deleteFromDatabase($vendor);

        return $this->redirectToIndex($request, 'admin.vendors.index', 'Vendor deleted successfully.');
    }

    public function cities(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
            'state' => ['nullable', 'string', 'max:255'],
        ]);

        $cities = City::query()
            ->where('country_id', $data['country_id'])
            ->when(($data['state'] ?? '') !== '', fn ($query) => $query->where('state', $data['state']))
            ->where('status', 'active')
            ->orderBy('city_name')
            ->get(['id', 'city_name', 'state']);

        return response()->json([
            'data' => $cities,
        ]);
    }

    public function states(Request $request): JsonResponse
    {
        $data = $request->validate([
            'country_id' => ['required', 'exists:countries,id'],
        ]);

        return response()->json([
            'data' => $this->stateOptions(true, (int) $data['country_id']),
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
            ->get(['id', 'zone_name', 'zone_code']);

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
            'pincode' => mb_strtoupper(trim((string) $request->input('pincode'))),
        ]);

        $data = $request->validate([
            'vendor_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^(?=.*[A-Za-z0-9])[A-Za-z0-9\s&.,\'()\-\/]+$/',
            ],
            'email' => [
                'required',
                'email',
                'regex:/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
                'max:255',
                Rule::unique('vendors', 'email')->ignore($vendor?->id),
            ],
            'role' => ['required', 'exists:roles,role_name'],
            'phone' => ['nullable', 'regex:/^[0-9]{10,15}$/'],
            'address' => ['nullable', 'string'],
            'pincode' => ['nullable', 'regex:/^[A-Za-z0-9 ]{3,12}$/'],
            'country_id' => ['required', 'exists:countries,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'region_zone_id' => ['nullable', 'exists:regions_zones,id'],
            'inventory_mode' => ['required', Rule::in(['internal', 'epos'])],
            'api_url' => ['nullable', 'string'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'credentials' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'vendor_name.regex' => 'Vendor name must include letters or numbers and cannot contain unsupported special characters.',
            'email.regex' => 'Enter a valid email address with domain and extension.',
            'phone.regex' => 'Phone must contain only 10 to 15 digits.',
            'pincode.regex' => 'Pincode must contain 3 to 12 letters or numbers.',
        ]);

        $city = City::findOrFail($data['city_id']);
        if ((int) $city->country_id !== (int) $data['country_id']) {
            throw ValidationException::withMessages([
                'city_id' => 'Selected city must belong to the selected country.',
            ]);
        }

        if (empty($data['region_zone_id'])) {
            $data['region_zone_id'] = $this->resolveVendorZoneId($data, $request->user()?->id);
        }

        if (empty($data['region_zone_id'])) {
            throw ValidationException::withMessages([
                'city_id' => 'Please add an active region or zone for the selected city before saving this vendor.',
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

    private function resolveVendorZoneId(array $data, ?int $userId): ?int
    {
        $zoneId = RegionZone::query()
            ->where('country_id', $data['country_id'])
            ->where('city_id', $data['city_id'])
            ->where('status', 'active')
            ->orderBy('zone_name')
            ->value('id');

        if ($zoneId) {
            return (int) $zoneId;
        }

        $zone = RegionZone::withTrashed()->firstOrNew([
            'country_id' => $data['country_id'],
            'city_id' => $data['city_id'],
            'zone_name' => 'City-wide',
        ]);

        if (! $zone->exists) {
            $zone->created_by = $userId;
        } elseif ($zone->trashed()) {
            $zone->restore();
        }

        $zone->fill([
            'zone_code' => null,
            'delivery_available' => true,
            'status' => 'active',
            'updated_by' => $userId,
        ]);
        $zone->save();

        return (int) $zone->id;
    }

    private function generatePassword(): string
    {
        return Str::password(12, true, true, false);
    }

    private function sendCredentialsMail(Vendor $vendor, ?string $plainPassword = null): bool
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

    private function stateOptions(bool $activeOnly = true, ?int $countryId = null): array
    {
        return City::query()
            ->when($activeOnly, fn ($query) => $query->where('status', 'active'))
            ->when($countryId, fn ($query) => $query->where('country_id', $countryId))
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

    private function cityOptions(bool $activeOnly = true): array
    {
        return City::query()
            ->when($activeOnly, fn ($query) => $query->where('status', 'active'))
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
