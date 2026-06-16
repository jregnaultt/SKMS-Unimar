<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicPeriod>
 */
class AcademicPeriodFactory extends Factory
{
    protected $model = AcademicPeriod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->startOfYear();
        $end = now()->endOfYear();

        return [
            'name' => fake()->unique()->randomElement([
                '2026-I',
                '2026-II',
                '2026-III',
                '2027-I',
                '2027-II',
                '2027-III',
            ]),
            'start_date' => $start,
            'end_date' => $end,
            'is_active' => true,
        ];
    }
}
