<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // First we have to drop the unique constraint so we can allow retakes without deleting history
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->enum('violation_appeal_status', ['diajukan', 'diterima', 'ditolak'])->nullable()->default(null);
            $table->text('violation_appeal_note')->nullable();
        });
        
        DB::statement("ALTER TABLE exam_attempts MODIFY COLUMN status ENUM('sedang_berjalan', 'selesai', 'selesai_pelanggaran', 'menunggu_penilaian_esai') DEFAULT 'sedang_berjalan'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE exam_attempts MODIFY COLUMN status ENUM('sedang_berjalan', 'selesai', 'selesai_pelanggaran', 'menunggu_penilaian_esai') DEFAULT 'sedang_berjalan'");
        
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn(['violation_appeal_status', 'violation_appeal_note']);
        });
    }
};
