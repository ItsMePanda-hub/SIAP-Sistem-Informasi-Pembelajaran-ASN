<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->unique()->after('id');
            $table->string('jabatan')->nullable()->after('name');
            $table->string('unit_kerja')->nullable()->after('jabatan');
            $table->enum('role', ['pegawai', 'atasan', 'pemilik', 'admin'])->default('pegawai')->after('unit_kerja');
            $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete()->after('role');
            $table->enum('status_kepegawaian', ['aktif', 'mode_terbatas', 'nonaktif'])->default('aktif')->after('atasan_id');
            $table->timestamp('simpeg_synced_at')->nullable()->after('status_kepegawaian');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atasan_id');
            $table->dropColumn(['nip', 'jabatan', 'unit_kerja', 'role', 'status_kepegawaian', 'simpeg_synced_at']);
        });
    }
};
