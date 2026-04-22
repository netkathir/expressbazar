<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->whereIn('role', Role::query()->pluck('role_name'))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->string('search'));
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'title' => 'Admin User Management',
            'activeMenu' => 'users',
            'users' => $users,
            'roles' => Role::orderBy('role_name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', [
            'title' => 'Add Admin User',
            'activeMenu' => 'users',
            'user' => new User(),
            'roles' => Role::orderBy('role_name')->get(),
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateUser($request);
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('admin.users.index')->with('success', 'Admin user created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'title' => 'Edit Admin User',
            'activeMenu' => 'users',
            'user' => $user,
            'roles' => Role::orderBy('role_name')->get(),
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validateUser($request, $user);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Admin user updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Admin user deleted successfully.');
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', Rule::exists('roles', 'role_name')],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }
}
