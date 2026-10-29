<?php

namespace Database\Seeders;

use App\Modules\Users\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@yallashop.com'],
            [
                'name' => 'Admin',
                'phone' => '+966500000000',
                'password' => 'password',
                'role' => UserRole::Admin,
                'status' => 'active',
            ]
        );

        $user->assignRole('admin');
    }
}
