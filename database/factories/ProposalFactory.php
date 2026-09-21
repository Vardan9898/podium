<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proposal>
 */
final class ProposalFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->speaker(),
            'title' => rtrim(fake()->sentence(5), '.'),
            'description' => fake()->paragraphs(3, true),
            'status' => ProposalStatus::Pending,
        ];
    }

    public function status(ProposalStatus $status): self
    {
        return $this->state(['status' => $status]);
    }

    public function approved(): self
    {
        return $this->status(ProposalStatus::Approved);
    }

    public function rejected(): self
    {
        return $this->status(ProposalStatus::Rejected);
    }

    /**
     * @param  int|iterable<Tag>  $tags  A count of new tags, or existing tags to attach.
     */
    public function withTags(int|iterable $tags = 2): self
    {
        return $this->afterCreating(fn (Proposal $proposal) => $proposal->tags()->attach(
            collect(is_int($tags) ? Tag::factory()->count($tags)->create() : $tags)->pluck('id'),
        ));
    }

    public function withAttachment(string $path = 'proposals/sample.pdf', string $name = 'slides.pdf'): self
    {
        return $this->state([
            'attachment_path' => $path,
            'attachment_original_name' => $name,
        ]);
    }
}
