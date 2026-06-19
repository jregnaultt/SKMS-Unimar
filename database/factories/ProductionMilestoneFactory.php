<?php

namespace Database\Factories;

use App\Models\Production;
use App\Models\ProductionMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionMilestone>
 */
class ProductionMilestoneFactory extends Factory
{
    protected $model = ProductionMilestone::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'production_id' => Production::factory(),
            'subject_id' => null,
            'type' => fake()->randomElement(['delivery', 'defense', 'pre_defense', 'system_defense']),
            'title' => fake()->sentence(3),
            'scheduled_date' => now()->addDays(fake()->numberBetween(1, 60)),
            'completed_date' => null,
            'status' => 'pending',
            'document_version_id' => null,
        ];
    }

    /**
     * Hito completado.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_date' => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }

    /**
     * Hito atrasado (missed).
     */
    public function missed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'missed',
            'scheduled_date' => now()->subDays(fake()->numberBetween(1, 10)),
            'completed_date' => null,
        ]);
    }
}
