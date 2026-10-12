<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_material_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')->unique()->constrained('learning_materials')->cascadeOnDelete();
            $table->string('source_name');
            $table->string('source_url')->nullable();
            $table->string('source_type')->nullable();
            $table->string('publisher')->nullable();
            $table->string('document_identifier')->nullable();
            $table->string('version')->nullable();
            $table->date('source_issued_at')->nullable();
            $table->string('license_type')->nullable();
            $table->string('license_url')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_sources');
    }
};
