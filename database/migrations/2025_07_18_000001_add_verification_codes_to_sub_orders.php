<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_orders', function (Blueprint $table) {
            $table->string('pickup_code', 6)->nullable()->after('courier_id');
            $table->string('delivery_code', 6)->nullable()->after('pickup_code');
            $table->json('delivery_address_snapshot')->nullable()->after('delivered_at');
        });
    }

    public function down(): void
    {
        Schema::table('sub_orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_code', 'delivery_code', 'delivery_address_snapshot']);
        });
    }
};
