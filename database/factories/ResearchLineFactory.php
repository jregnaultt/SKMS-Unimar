<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use App\Models\ResearchLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ResearchLine>
 */
class ResearchLineFactory extends Factory
{
    protected $model = ResearchLine::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_program_id' => AcademicProgram::factory(),
            'name' => fake()->unique()->randomElement([
                'Inteligencia Artificial y Ciencia de Datos',
                'Desarrollo de Software y Sistemas Embebidos',
                'Optimización de Procesos y Operaciones',
                'Gestión de Calidad y Productividad',
                'Derecho Constitucional y Derechos Humanos',
                'Lingüística Aplicada y Traducción',
            ]),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
