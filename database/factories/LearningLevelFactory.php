<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LearningLevel>
 */
class LearningLevelFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'key' => Str::slug($name),
            'name' => Str::title($name),
            'slug' => Str::slug($name) . '-' . Str::random(4),
            'description' => fake()->sentence(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }

    public function pemula(): static
    {
        return $this->state(fn () => ['key' => 'pemula', 'name' => 'Pemula', 'slug' => 'pemula', 'sort_order' => 1]);
    }

    public function dasar(): static
    {
        return $this->state(fn () => ['key' => 'dasar', 'name' => 'Dasar', 'slug' => 'dasar', 'sort_order' => 2]);
    }

    public function menengah(): static
    {
        return $this->state(fn () => ['key' => 'menengah', 'name' => 'Menengah', 'slug' => 'menengah', 'sort_order' => 3]);
    }

    public function lanjutan(): static
    {
        return $this->state(fn () => ['key' => 'lanjutan', 'name' => 'Lanjutan', 'slug' => 'lanjutan', 'sort_order' => 4]);
    }

    public function mahir(): static
    {
        return $this->state(fn () => ['key' => 'mahir', 'name' => 'Mahir', 'slug' => 'mahir', 'sort_order' => 5]);
    }
}
