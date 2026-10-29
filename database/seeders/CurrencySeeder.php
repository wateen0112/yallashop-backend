<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::updateOrCreate(
            ['code' => 'SAR'],
            [
                'name' => 'Saudi Riyal',
                'symbol' => 'ر.س',
                'exchange_rate' => 1.0,
                'is_default' => true,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'symbol' => '$',
                'exchange_rate' => 0.2667,
                'is_default' => false,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'EUR'],
            [
                'name' => 'Euro',
                'symbol' => '€',
                'exchange_rate' => 0.2467,
                'is_default' => false,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'AED'],
            [
                'name' => 'UAE Dirham',
                'symbol' => 'د.إ',
                'exchange_rate' => 0.9800,
                'is_default' => false,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'EGP'],
            [
                'name' => 'Egyptian Pound',
                'symbol' => 'ج.م',
                'exchange_rate' => 12.9200,
                'is_default' => false,
            ]
        );
    }
}
