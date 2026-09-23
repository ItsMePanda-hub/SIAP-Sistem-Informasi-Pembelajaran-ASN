<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pegawai', 'atasan', 'pemilik', 'admin') DEFAULT 'pegawai'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pegawai', 'atasan', 'admin') DEFAULT 'pegawai'");
    }
};
