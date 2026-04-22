<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'username',
        'phone',
        'password',
        'role',
        'status',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->status === 'active' && $this->adminRole() !== null;
    }

    public function adminRole(): ?Role
    {
        return $this->role ? Role::query()->with('permissions')->where('role_name', $this->role)->first() : null;
    }

    public function hasRolePermission(string $moduleKey, string $ability = 'view'): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        $role = $this->adminRole();

        if (! $role || $role->status !== 'active') {
            return false;
        }

        $permission = $role->permissions->firstWhere('module_name', self::moduleLabel($moduleKey));

        if (! $permission) {
            return false;
        }

        return match ($ability) {
            'create' => $permission->can_create,
            'edit' => $permission->can_edit,
            'delete' => $permission->can_delete,
            default => $permission->can_view,
        };
    }

    public function canAccessAdminRoute(?string $routeName, string $method = 'GET'): bool
    {
        if (! $routeName) {
            return true;
        }

        $moduleKey = self::routeToModuleKey($routeName);

        if (! $moduleKey) {
            return true;
        }

        $ability = self::abilityFromRoute($routeName, $method);

        return $this->hasRolePermission($moduleKey, $ability);
    }

    private static function abilityFromRoute(string $routeName, string $method): string
    {
        if ($method === 'POST') {
            return str_contains($routeName, 'toggle-status') ? 'edit' : 'create';
        }

        if (in_array($method, ['PUT', 'PATCH'])) {
            return 'edit';
        }

        if ($method === 'DELETE') {
            return 'delete';
        }

        return 'view';
    }

    private static function routeToModuleKey(string $routeName): ?string
    {
        $prefix = explode('.', $routeName)[1] ?? null;

        return match ($prefix) {
            'countries' => 'countries',
            'cities' => 'cities',
            'zones' => 'zones',
            'vendors' => 'vendors',
            'categories' => 'categories',
            'subcategories' => 'subcategories',
            'customers' => 'customers',
            'taxes' => 'taxes',
            'products' => 'products',
            'inventory' => 'inventory',
            'orders' => 'orders',
            'payments' => 'payments',
            'delivery' => 'delivery',
            'notifications' => 'notifications',
            'reports' => 'reports',
            'roles' => 'roles',
            'users' => 'users',
            'system-config' => 'config',
            default => null,
        };
    }

    private static function moduleLabel(string $moduleKey): string
    {
        return match ($moduleKey) {
            'countries' => 'Country Management',
            'cities' => 'City Management',
            'zones' => 'Region / Zone Management',
            'vendors' => 'Vendor Management',
            'categories' => 'Category Management',
            'subcategories' => 'Subcategory Management',
            'customers' => 'Customer Management',
            'taxes' => 'Tax Management',
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
            default => $moduleKey,
        };
    }
}
