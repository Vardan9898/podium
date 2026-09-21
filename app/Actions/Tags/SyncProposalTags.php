<?php

declare(strict_types=1);

namespace App\Actions\Tags;

use App\Models\Proposal;
use App\Models\Tag;

final class SyncProposalTags
{
    /**
     * Resolves tag names to tags (creating missing ones) and syncs them onto the proposal.
     *
     * insertOrIgnore + re-select keeps this race-safe: two requests creating the
     * same new tag concurrently both end up pointing at the single stored row.
     *
     * @param  list<string>  $names
     */
    public function handle(Proposal $proposal, array $names): void
    {
        $tags = collect($names)
            ->map(fn (string $name): string => Tag::normalizeName($name))
            ->filter(fn (string $name): bool => $name !== '')
            ->unique(fn (string $name): string => Tag::keyFor($name))
            ->keyBy(fn (string $name): string => Tag::keyFor($name));

        if ($tags->isNotEmpty()) {
            $now = now();

            Tag::query()->insertOrIgnore(
                $tags->map(fn (string $name, string $key): array => [
                    'name' => $name,
                    'normalized_name' => $key,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all(),
            );
        }

        $proposal->tags()->sync(
            Tag::query()->whereIn('normalized_name', $tags->keys())->pluck('id'),
        );
    }
}
