<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_material_lifecycle_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_material_id')->nullable()->constrained('learning_materials')->nullOnDelete();
            $table->string('title_snapshot')->nullable();
            $table->string('slug_snapshot')->nullable();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('reason')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['learning_material_id', 'created_at'], 'lm_lifecycle_mat_created_idx');
            $table->index('field', 'lm_lifecycle_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_material_lifecycle_logs');
    }
};
