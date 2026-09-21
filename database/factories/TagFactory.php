<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
final class TagFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->word()).' '.fake()->randomNumber(3);

        return [
            'name' => $name,
            'normalized_name' => Tag::keyFor($name),
        ];
    }

    public function named(string $name): self
    {
        return $this->state(['name' => $name, 'normalized_name' => Tag::keyFor($name)]);
    }
}
