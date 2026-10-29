<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->whereNull('currency_id')
            ->update([
                'currency_id' => DB::table('currencies')
                    ->where('is_default', true)
                    ->value('id'),
            ]);
    }

    public function down(): void
    {
        DB::table('products')
            ->update(['currency_id' => null]);
    }
};
