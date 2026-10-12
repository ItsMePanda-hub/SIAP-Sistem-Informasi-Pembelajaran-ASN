<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('material_type');
            $table->string('scope');
            $table->foreignId('learning_level_id')->constrained('learning_levels')->restrictOnDelete();
            $table->foreignId('learning_category_id')->constrained('learning_categories')->restrictOnDelete();
            $table->string('publication_status')->default('draft');
            $table->string('verification_status')->default('draft');
            $table->string('validity_status')->default('unknown');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['scope', 'publication_status']);
            $table->index('verification_status');
            $table->index('validity_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_materials');
    }
};
