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
        'avatar_path',
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
        $role = $this->adminRole();

        return $this->status === 'active' && $role !== null && $role->status === 'active';
    }

    public function adminRole(): ?Role
    {
        if (! $this->role) {
            return null;
        }

        return $this->relationLoaded('adminRoleRecord')
            ? $this->getRelation('adminRoleRecord')
            : Role::query()->with('permissions')->where('role_name', $this->role)->first();
    }

    public function adminRoleRecord()
    {
        return $this->belongsTo(Role::class, 'role', 'role_name')->with('permissions');
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

        if (strtolower(trim($role->role_name)) === 'admin') {
            return true;
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

    public function hasPermission(string $permission): bool
    {
        [$moduleKey, $ability] = array_pad(explode('-', $permission, 2), 2, 'view');

        return $this->hasRolePermission($moduleKey, $ability ?: 'view');
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

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'user_id');
    }

    public function wishlists()
    {
        return $this->hasMany(CustomerWishlist::class, 'user_id');
    }

    private static function abilityFromRoute(string $routeName, string $method): string
    {
        if (str_contains($routeName, '.toggle-status') || str_contains($routeName, '.read-all')) {
            return 'edit';
        }

        if (str_contains($routeName, '.bulk')) {
            return 'create';
        }

        if (str_ends_with($routeName, '.create') || str_ends_with($routeName, '.store')) {
            return 'create';
        }

        if (str_ends_with($routeName, '.edit') || str_ends_with($routeName, '.update')) {
            return 'edit';
        }

        if (str_ends_with($routeName, '.destroy') || str_contains($routeName, '.images.destroy')) {
            return 'delete';
        }

        if ($method === 'POST') {
            return 'create';
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
            'coupons' => 'coupons',
            'products' => 'products',
            'inventory' => 'inventory',
            'orders' => 'orders',
            'payments' => 'payments',
            'delivery' => 'delivery',
            'notifications' => 'notifications',
            'contacts' => 'contacts',
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
            'coupons' => 'Coupon Management',
            'products' => 'Product Management',
            'inventory' => 'Inventory Management',
            'orders' => 'Order Management',
            'payments' => 'Payment Management',
            'delivery' => 'Delivery & Logistics',
            'notifications' => 'Notification Management',
            'contacts' => 'Contact Inquiries',
            'reports' => 'Reports & Analytics',
            'roles' => 'User & Role Management',
            'users' => 'Admin User Management',
            'config' => 'System Configuration',
            default => $moduleKey,
        };
    }
}
