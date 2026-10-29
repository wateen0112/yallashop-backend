<?php

namespace Database\Seeders;

use App\Modules\Users\Models\CustomerProfile;
use App\Modules\Users\Models\User;
use App\Support\Enums\UserRole;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    private const CUSTOMERS = [
        [
            'name' => 'Ahmed Ali',
            'email' => 'ahmed@yallashop.com',
            'phone' => '+966501000001',
            'password' => 'password',
            'wallet_balance' => 250.00,
            'loyalty_points' => 120,
        ],
        [
            'name' => 'Sara Hassan',
            'email' => 'sara@yallashop.com',
            'phone' => '+966501000002',
            'password' => 'password',
            'wallet_balance' => 500.00,
            'loyalty_points' => 300,
        ],
        [
            'name' => 'Mohammed Khaled',
            'email' => 'mohammed@yallashop.com',
            'phone' => '+966501000003',
            'password' => 'password',
            'wallet_balance' => 75.50,
            'loyalty_points' => 45,
        ],
        [
            'name' => 'Fatima Nasser',
            'email' => 'fatima@yallashop.com',
            'phone' => '+966501000004',
            'password' => 'password',
            'wallet_balance' => 1000.00,
            'loyalty_points' => 500,
        ],
        [
            'name' => 'Omar Youssef',
            'email' => 'omar@yallashop.com',
            'phone' => '+966501000005',
            'password' => 'password',
            'wallet_balance' => 0.00,
            'loyalty_points' => 0,
        ],
    ];

    public function run(): void
    {
        foreach (self::CUSTOMERS as $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'role' => UserRole::Customer,
            ]);

            $user->assignRole('customer');

            CustomerProfile::create([
                'user_id' => $user->id,
                'wallet_balance' => $data['wallet_balance'],
                'loyalty_points' => $data['loyalty_points'],
            ]);
        }
    }
}
