<?php

namespace Database\Factories;

use App\Models\ProductionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductionType>
 */
class ProductionTypeFactory extends Factory
{
    protected $model = ProductionType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Trabajo Especial de Grado',
                'Proyecto de Investigación',
                'Artículo Científico',
                'Ponencia de Congreso',
                'Capítulo de Libro',
            ]),
            'description' => fake()->sentence(),
        ];
    }
}
