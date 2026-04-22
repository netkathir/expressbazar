<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_name',
        'description',
        'status',
    ];

    public function permissions()
    {
        return $this->hasMany(RolePermission::class);
    }
}
