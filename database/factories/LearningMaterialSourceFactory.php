<?php

namespace Database\Factories;

use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningMaterialSource>
 */
class LearningMaterialSourceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'learning_material_id' => LearningMaterial::factory(),
            'source_name' => fake()->company(),
            'source_url' => fake()->url(),
            'source_type' => fake()->randomElement(['peraturan', 'buku_pedoman', 'artikel', 'laporan']),
            'publisher' => fake()->company(),
            'document_identifier' => 'DOC-' . fake()->unique()->numerify('###/###'),
            'version' => 'v' . fake()->numerify('#.#'),
            'source_issued_at' => fake()->date(),
            'license_type' => 'cc-by-4.0',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'last_checked_at' => now(),
            'verification_notes' => fake()->sentence(),
            'rights_status' => 'approved',
            'rights_reviewed_by' => fn () => User::factory()->create(['role' => 'admin_library'])->id,
            'rights_reviewed_at' => now(),
            'rights_review_notes' => 'Hak cipta dan izin redistribusi diverifikasi.',
            'metadata' => ['checked_by' => fake()->name()],
        ];
    }

    public function unreviewed(): static
    {
        return $this->state(fn () => [
            'rights_status' => 'unreviewed',
            'rights_reviewed_by' => null,
            'rights_reviewed_at' => null,
            'rights_review_notes' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'rights_status' => 'rejected',
            'rights_reviewed_by' => fn () => User::factory()->create(['role' => 'admin_library'])->id,
            'rights_reviewed_at' => now(),
            'rights_review_notes' => 'Izin redistribusi ditolak.',
        ]);
    }

    public function hakCipta(): static
    {
        return $this->state(fn () => [
            'license_type' => 'hak_cipta',
            'license_url' => null,
        ]);
    }

    public function tidakDiketahui(): static
    {
        return $this->state(fn () => [
            'license_type' => 'tidak_diketahui',
            'license_url' => null,
        ]);
    }
}
