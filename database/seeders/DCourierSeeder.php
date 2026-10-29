<?php

namespace Database\Seeders;

use App\Modules\Users\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Database\Seeder;

class DCourierSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'd@d.c'],
            [
                'name' => 'D',
                'phone' => '+966502000001',
                'password' => 'password',
                'role' => UserRole::Courier,
                'status' => 'active',
            ]
        );

        $user->assignRole('courier');
    }
}