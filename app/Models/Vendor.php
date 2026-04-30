<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Vendor extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'vendor_name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'pincode',
        'logo_path',
        'country_id',
        'city_id',
        'region_zone_id',
        'zone_id',
        'inventory_mode',
        'api_url',
        'api_key',
        'credentials',
        'status',
        'setup_token',
        'is_setup_complete',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'is_setup_complete' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function displayName(): string
    {
        return $this->vendor_name;
    }

    public function roleRecord(): ?Role
    {
        return $this->role ? Role::query()->with('permissions')->where('role_name', $this->role)->first() : null;
    }

    public function hasRolePermission(string $moduleKey, string $ability = 'view'): bool
    {
        if (! $this->isActive()) {
            return false;
        }

        $role = $this->roleRecord();

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

    public function canAccessVendorRoute(?string $routeName, string $method = 'GET'): bool
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

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function zone()
    {
        return $this->belongsTo(RegionZone::class, 'region_zone_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    private static function abilityFromRoute(string $routeName, string $method): string
    {
        if (str_contains($routeName, '.accept') || str_contains($routeName, '.reject')) {
            return 'edit';
        }

        if (str_contains($routeName, '.processing') || str_contains($routeName, '.dispatched') || str_contains($routeName, '.delivered')) {
            return 'edit';
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

        if (in_array($method, ['PUT', 'PATCH'])) {
            return 'edit';
        }

        if ($method === 'DELETE') {
            return 'delete';
        }

        if ($method === 'POST') {
            return 'create';
        }

        return 'view';
    }

    private static function routeToModuleKey(string $routeName): ?string
    {
        $prefix = explode('.', $routeName)[1] ?? null;

        return match ($prefix) {
            'products' => 'products',
            'orders' => 'orders',
            'coupons' => 'coupons',
            'payments' => 'payments',
            default => null,
        };
    }

    private static function moduleLabel(string $moduleKey): string
    {
        return match ($moduleKey) {
            'products' => 'Product Management',
            'orders' => 'Order Management',
            'coupons' => 'Coupon Management',
            'payments' => 'Payment Management',
            default => $moduleKey,
        };
    }
}
