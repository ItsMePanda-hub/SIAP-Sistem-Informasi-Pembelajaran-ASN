<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('letters')
            ->where('target_role', 'pengguna')
            ->update(['target_role' => 'pegawai']);

        DB::statement("ALTER TABLE letters MODIFY COLUMN target_role ENUM('admin', 'pemilik', 'atasan', 'pegawai') NULL");
    }

    public function down(): void
    {
        DB::table('letters')
            ->where('target_role', 'pegawai')
            ->update(['target_role' => 'pengguna']);

        DB::statement("ALTER TABLE letters MODIFY COLUMN target_role ENUM('admin', 'pemilik', 'atasan', 'pengguna') NULL");
    }
};
