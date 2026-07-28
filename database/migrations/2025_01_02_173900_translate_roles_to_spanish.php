<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->where('name', 'technician')->update(['name' => 'técnico']);
        DB::table('roles')->where('name', 'customer')->update(['name' => 'cliente']);
        DB::table('roles')->where('name', 'admin')->update(['name' => 'administrador']);
    }

    public function down(): void
    {
        DB::table('roles')->where('name', 'técnico')->update(['name' => 'technician']);
        DB::table('roles')->where('name', 'cliente')->update(['name' => 'customer']);
        DB::table('roles')->where('name', 'administrador')->update(['name' => 'admin']);
    }
};
