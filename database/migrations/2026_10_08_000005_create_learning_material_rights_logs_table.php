<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_material_rights_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_source_id')
                ->constrained('learning_material_sources', 'id', 'lm_rights_logs_source_id_fk')
                ->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')
                ->nullable()
                ->constrained('users', 'id', 'lm_rights_logs_reviewer_id_fk')
                ->nullOnDelete();
            $table->string('decision');
            $table->string('license_type')->nullable();
            $table->string('license_url')->nullable();
            $table->string('source_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_rights_logs');
    }
};
