<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('learning_material_sources', function (Blueprint $table) {
            $table->string('rights_status')->default('unreviewed')->after('license_url');
            $table->foreignId('rights_reviewed_by')->nullable()->after('rights_status')->constrained('users')->nullOnDelete();
            $table->timestamp('rights_reviewed_at')->nullable()->after('rights_reviewed_by');
            $table->text('rights_review_notes')->nullable()->after('rights_reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('learning_material_sources', function (Blueprint $table) {
            $table->dropForeign(['rights_reviewed_by']);
            $table->dropColumn(['rights_status', 'rights_reviewed_by', 'rights_reviewed_at', 'rights_review_notes']);
        });
    }
};
