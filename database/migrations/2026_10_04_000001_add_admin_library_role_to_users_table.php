<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::create('users_tmp_phase21', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
                $table->string('nip')->nullable()->unique();
                $table->string('jabatan')->nullable();
                $table->string('unit_kerja')->nullable();
                $table->string('role')->default('pegawai');
                $table->foreignId('atasan_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status_kepegawaian')->default('aktif');
                $table->timestamp('simpeg_synced_at')->nullable();
            });
            DB::statement('INSERT INTO users_tmp_phase21 (id, name, email, email_verified_at, password, remember_token, created_at, updated_at, nip, jabatan, unit_kerja, role, atasan_id, status_kepegawaian, simpeg_synced_at) SELECT id, name, email, email_verified_at, password, remember_token, created_at, updated_at, nip, jabatan, unit_kerja, role, atasan_id, status_kepegawaian, simpeg_synced_at FROM users');
            Schema::drop('users');
            Schema::rename('users_tmp_phase21', 'users');
            return;
        }

        $hasEnum = false;
        try {
            $row = DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'role'");
            $hasEnum = $row && str_contains((string) ($row->Type ?? ''), 'enum');
        } catch (\Throwable $e) {
            $hasEnum = false;
        }

        if ($hasEnum) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pegawai','atasan','pemilik','admin','admin_library') DEFAULT 'pegawai'");
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(255) DEFAULT 'pegawai'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $hasEnum = false;
        try {
            $row = DB::selectOne("SHOW COLUMNS FROM users WHERE Field = 'role'");
            $hasEnum = $row && str_contains((string) ($row->Type ?? ''), 'enum');
        } catch (\Throwable $e) {
            $hasEnum = false;
        }

        if ($hasEnum) {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('pegawai','atasan','pemilik','admin') DEFAULT 'pegawai'");
        } else {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(255) DEFAULT 'pegawai'");
        }
    }
};
