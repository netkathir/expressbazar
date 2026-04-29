<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private array $modules = [
        'countries' => 'Country Management',
        'cities' => 'City Management',
        'zones' => 'Region / Zone Management',
        'vendors' => 'Vendor Management',
        'categories' => 'Category Management',
        'subcategories' => 'Subcategory Management',
        'customers' => 'Customer Management',
        'taxes' => 'Tax Management',
        'coupons' => 'Coupon Management',
        'products' => 'Product Management',
        'inventory' => 'Inventory Management',
        'orders' => 'Order Management',
        'payments' => 'Payment Management',
        'delivery' => 'Delivery & Logistics',
        'notifications' => 'Notification Management',
        'reports' => 'Reports & Analytics',
        'roles' => 'User & Role Management',
        'users' => 'Admin User Management',
        'config' => 'System Configuration',
    ];

    public function index()
    {
        $permissionCountQuery = RolePermission::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN can_view = 1 OR can_create = 1 OR can_edit = 1 OR can_delete = 1 THEN 1 ELSE 0 END), 0)')
            ->whereColumn('role_permissions.role_id', 'roles.id');

        return view('admin.roles.index', [
            'title' => 'User & Role Management',
            'activeMenu' => 'roles',
            'roles' => Role::query()
                ->with('permissions')
                ->select('roles.*')
                ->selectSub($permissionCountQuery, 'permissions_count')
                ->latest()
                ->paginate(10),
            'modules' => $this->modules,
        ]);
    }

    public function create()
    {
        return view('admin.roles.form', [
            'title' => 'Add Role',
            'activeMenu' => 'roles',
            'role' => new Role(),
            'modules' => $this->modules,
            'mode' => 'create',
        ]);
    }

    public function store(Request $request)
    {
        [$role, $permissions] = $this->validateAndNormalize($request);

        DB::transaction(function () use ($role, $permissions) {
            $created = Role::create($role);
            $this->syncPermissions($created, $permissions);
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $role->load('permissions');

        return view('admin.roles.form', [
            'title' => 'Edit Role',
            'activeMenu' => 'roles',
            'role' => $role,
            'modules' => $this->modules,
            'mode' => 'edit',
        ]);
    }

    public function update(Request $request, Role $role)
    {
        [$roleData, $permissions] = $this->validateAndNormalize($request, $role);

        DB::transaction(function () use ($role, $roleData, $permissions) {
            $role->update($roleData);
            $role->permissions()->delete();
            $this->syncPermissions($role, $permissions);
        });

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }

    private function validateAndNormalize(Request $request, ?Role $role = null): array
    {
        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:255', Rule::unique('roles', 'role_name')->ignore($role?->id)],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'permissions' => ['nullable', 'array'],
        ]);

        return [
            [
                'role_name' => $validated['role_name'],
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'],
            ],
            $validated['permissions'] ?? [],
        ];
    }

    private function syncPermissions(Role $role, array $permissions): void
    {
        if ($this->isAdminRole($role->role_name)) {
            $permissions = $this->fullPermissions();
        }

        foreach ($this->modules as $moduleKey => $moduleLabel) {
            $modulePermissions = $permissions[$moduleKey] ?? [];

            RolePermission::create([
                'role_id' => $role->id,
                'module_name' => $moduleLabel,
                'can_view' => (bool) ($modulePermissions['view'] ?? false),
                'can_create' => (bool) ($modulePermissions['create'] ?? false),
                'can_edit' => (bool) ($modulePermissions['edit'] ?? false),
                'can_delete' => (bool) ($modulePermissions['delete'] ?? false),
            ]);
        }
    }

    private function isAdminRole(string $roleName): bool
    {
        return strtolower(trim($roleName)) === 'admin';
    }

    private function fullPermissions(): array
    {
        return collect(array_keys($this->modules))
            ->mapWithKeys(fn ($moduleKey) => [
                $moduleKey => [
                    'view' => true,
                    'create' => true,
                    'edit' => true,
                    'delete' => true,
                ],
            ])
            ->all();
    }
}
