<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE exam_attempts MODIFY COLUMN status ENUM('sedang_berjalan', 'selesai', 'selesai_pelanggaran', 'menunggu_penilaian_esai') DEFAULT 'sedang_berjalan'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE exam_attempts MODIFY COLUMN status ENUM('sedang_berjalan', 'selesai', 'selesai_pelanggaran') DEFAULT 'sedang_berjalan'");
        }
    }
};
