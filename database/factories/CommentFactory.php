<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Production;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array{
     *     production_id: int,
     *     user_id: int,
     *     content: string,
     *     reference_section: string,
     *     status: string,
     *     parent_id: null
     * }
     */
    public function definition(): array
    {
        return [
            'production_id' => Production::factory(),
            'user_id' => User::factory(),
            'content' => fake()->paragraph(2),
            'reference_section' => fake()->randomElement([
                'Página '.fake()->numberBetween(1, 80),
                'Sección '.fake()->numberBetween(1, 5).'.'.fake()->numberBetween(1, 10),
                'Resumen',
                'Introducción',
                'Marco teórico',
                'Metodología',
                'Conclusiones',
            ]),
            'status' => CommentStatus::Pending->value,
            'parent_id' => null,
        ];
    }

    /**
     * State for a pending observation (default).
     */
    public function pending(): static
    {
        return $this->state(['status' => CommentStatus::Pending->value]);
    }

    /**
     * State for an in-progress observation.
     */
    public function inProgress(): static
    {
        return $this->state(['status' => CommentStatus::InProgress->value]);
    }

    /**
     * State for an addressed (resolved) observation.
     */
    public function addressed(): static
    {
        return $this->state(['status' => CommentStatus::Addressed->value]);
    }

    /**
     * State for a reply to an existing comment.
     */
    public function asReply(Comment $parent): static
    {
        return $this->state([
            'parent_id' => $parent->id,
            'production_id' => $parent->production_id,
            'status' => CommentStatus::Pending->value,
        ]);
    }
}
