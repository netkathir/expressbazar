<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@expressbazar.local'],
            [
                'name' => 'Express Bazar Admin',
                'username' => 'admin',
                'avatar_path' => '/admin-theme/assets/images/avatar/avatar-1.jpg',
                'password' => Hash::make('Admin@1234'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );
    }
}
