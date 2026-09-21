<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
final class ReviewFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'proposal_id' => Proposal::factory(),
            'user_id' => User::factory()->reviewer(),
            'rating' => fake()->numberBetween(
                config()->integer('proposals.rating.min'),
                config()->integer('proposals.rating.max'),
            ),
            'comment' => fake()->sentences(2, true),
        ];
    }
}
