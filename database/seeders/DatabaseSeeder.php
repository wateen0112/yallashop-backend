<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CurrencySeeder::class,
            RolePermissionSeeder::class,
            AdminSeeder::class,
            TestDataSeeder::class,
            CustomerSeeder::class,
            DCourierSeeder::class,
        ]);
    }
}
