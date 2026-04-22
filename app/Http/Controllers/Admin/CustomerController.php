<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.customers.index', [
            'title' => 'Customer Management',
            'activeMenu' => 'customers',
            'customers' => $customers,
        ]);
    }

    public function create()
    {
        return view('admin.customers.form', [
            'title' => 'Add Customer',
            'activeMenu' => 'customers',
            'customer' => new User(['role' => 'customer', 'status' => 'active']),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateCustomer($request);
        $password = Str::random(12);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($password),
            'role' => 'customer',
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Customer created successfully.');
    }

    public function show(User $customer)
    {
        abort_if($customer->role !== 'customer', 404);

        return view('admin.customers.show', [
            'title' => 'Customer Details',
            'activeMenu' => 'customers',
            'customer' => $customer,
        ]);
    }

    public function edit(User $customer)
    {
        abort_if($customer->role !== 'customer', 404);

        return view('admin.customers.form', [
            'title' => 'Edit Customer',
            'activeMenu' => 'customers',
            'customer' => $customer,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, User $customer)
    {
        abort_if($customer->role !== 'customer', 404);

        $data = $this->validateCustomer($request, $customer);

        $customer->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(User $customer)
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->delete();

        return redirect()->route('admin.customers.index')->with('success', 'Customer deleted successfully.');
    }

    public function toggleStatus(User $customer)
    {
        abort_if($customer->role !== 'customer', 404);

        $customer->update([
            'status' => $customer->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Customer status updated.');
    }

    private function validateCustomer(Request $request, ?User $customer = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($customer?->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
    }
}
