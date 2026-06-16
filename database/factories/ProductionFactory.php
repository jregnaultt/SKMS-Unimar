<?php

namespace Database\Factories;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\Production;
use App\Models\ProductionType;
use App\Models\ResearchLine;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Production>
 */
class ProductionFactory extends Factory
{
    protected $model = Production::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'title' => fake()->sentence(6),
            'abstract' => fake()->paragraph(4),
            'authors' => fake()->name().', '.fake()->name(),
            'tutor' => fake()->name(),
            'academic_program_id' => AcademicProgram::factory(),
            'research_line_id' => function (array $attributes) {
                return ResearchLine::factory()->create([
                    'academic_program_id' => $attributes['academic_program_id'],
                ])->id;
            },
            'production_type_id' => ProductionType::factory(),
            'academic_period_id' => AcademicPeriod::factory(),
            'workflow_state' => 'draft',
            'doi' => '10.'.fake()->numberBetween(1000, 9999).'/'.fake()->lexify('?????'),
            'submission_date' => null,
            'approval_date' => null,
        ];
    }

    /**
     * State for a production under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_state' => 'under_review',
            'submission_date' => now(),
        ]);
    }

    /**
     * State for a production needing corrections.
     */
    public function needsCorrections(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_state' => 'needs_corrections',
            'submission_date' => now(),
        ]);
    }

    /**
     * State for an approved production.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_state' => 'approved',
            'submission_date' => now()->subDays(5),
            'approval_date' => now(),
        ]);
    }

    /**
     * State for a published production.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'workflow_state' => 'published',
            'submission_date' => now()->subDays(10),
            'approval_date' => now()->subDays(5),
        ]);
    }
}
