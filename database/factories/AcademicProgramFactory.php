<?php

namespace Database\Factories;

use App\Models\AcademicProgram;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicProgram>
 */
class AcademicProgramFactory extends Factory
{
    protected $model = AcademicProgram::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Ingeniería de Sistemas',
            'Ingeniería Industrial',
            'Administración de Empresas',
            'Contaduría Pública',
            'Derecho',
            'Idiomas Modernos',
        ]);

        $codes = [
            'Ingeniería de Sistemas' => 'ING-SIS',
            'Ingeniería Industrial' => 'ING-IND',
            'Administración de Empresas' => 'ADM-EMP',
            'Contaduría Pública' => 'CON-PUB',
            'Derecho' => 'DER',
            'Idiomas Modernos' => 'IDI-MOD',
        ];

        return [
            'name' => $name,
            'code' => $codes[$name] ?? fake()->unique()->lexify('???-???'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
