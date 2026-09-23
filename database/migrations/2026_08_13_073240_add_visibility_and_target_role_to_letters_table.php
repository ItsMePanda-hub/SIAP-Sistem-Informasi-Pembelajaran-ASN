<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->enum('visibility', ['all', 'unit', 'role'])->nullable()->after('category');
            $table->enum('target_role', ['admin', 'pemilik', 'atasan', 'pengguna', 'pegawai'])->nullable()->after('target_unit_kerja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'target_role']);
        });
    }
};
