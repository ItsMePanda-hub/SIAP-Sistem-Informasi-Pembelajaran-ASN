<?php

namespace Database\Factories;

use App\Enums\LearningMaterialScope;
use App\Enums\LearningMaterialType;
use App\Enums\LearningPublicationStatus;
use App\Enums\LearningValidityStatus;
use App\Enums\LearningVerificationStatus;
use App\Models\LearningCategory;
use App\Models\LearningLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class LearningMaterialFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(4);
        $type = fake()->randomElement(LearningMaterialType::values());

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . Str::random(4),
            'description' => fake()->paragraph(),
            'content' => $type === LearningMaterialType::Native->value ? fake()->paragraphs(3, true) : null,
            'file_path' => $type === LearningMaterialType::Pdf->value ? 'library-pdfs/' . Str::random(10) . '.pdf' : null,
            'file_original_name' => $type === LearningMaterialType::Pdf->value ? 'dokumen.pdf' : null,
            'file_mime' => $type === LearningMaterialType::Pdf->value ? 'application/pdf' : null,
            'file_size' => $type === LearningMaterialType::Pdf->value ? fake()->numberBetween(10000, 500000) : null,
            'material_type' => $type,
            'scope' => fake()->randomElement(LearningMaterialScope::values()),
            'learning_level_id' => LearningLevel::factory(),
            'learning_category_id' => LearningCategory::factory(),
            'publication_status' => LearningPublicationStatus::Draft->value,
            'verification_status' => LearningVerificationStatus::Draft->value,
            'validity_status' => LearningValidityStatus::Unknown->value,
            'published_at' => null,
            'uploaded_by' => null,
        ];
    }

    public function core(): static
    {
        return $this->state(fn () => ['scope' => LearningMaterialScope::Core->value]);
    }

    public function custom(): static
    {
        return $this->state(fn () => ['scope' => LearningMaterialScope::Custom->value]);
    }

    public function native(): static
    {
        return $this->state(fn () => [
            'material_type' => LearningMaterialType::Native->value,
            'content' => fake()->paragraphs(3, true),
            'file_path' => null,
            'file_original_name' => null,
            'file_mime' => null,
            'file_size' => null,
        ]);
    }

    public function pdf(): static
    {
        return $this->state(fn () => [
            'material_type' => LearningMaterialType::Pdf->value,
            'content' => null,
            'file_path' => 'library-pdfs/' . Str::random(10) . '.pdf',
            'file_original_name' => 'dokumen.pdf',
            'file_mime' => 'application/pdf',
            'file_size' => fake()->numberBetween(10000, 500000),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'publication_status' => LearningPublicationStatus::Draft->value,
            'published_at' => null,
        ]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'verification_status' => LearningVerificationStatus::Verified->value,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'publication_status' => LearningPublicationStatus::Published->value,
            'published_at' => now(),
            'verification_status' => LearningVerificationStatus::Verified->value,
            'validity_status' => LearningValidityStatus::Active->value,
        ]);
    }

    public function withUploader(?User $user = null): static
    {
        return $this->state(fn () => [
            'uploaded_by' => $user?->id ?? User::factory(),
        ]);
    }
}
