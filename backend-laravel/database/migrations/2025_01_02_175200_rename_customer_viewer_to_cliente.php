<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('name', 'customer_viewer')->update(['name' => 'cliente']);
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'cliente')->update(['name' => 'customer_viewer']);
    }
};
