<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')->where('status', 'approved')->update(['status' => 'active']);
        DB::table('products')->where('status', 'disabled')->update(['status' => 'archived']);
        DB::table('products')->where('status', 'inactive')->update(['status' => 'draft']);
    }

    public function down(): void
    {
    }
};
