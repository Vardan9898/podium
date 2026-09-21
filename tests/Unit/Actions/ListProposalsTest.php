<?php

declare(strict_types=1);

use App\Actions\Proposals\ListProposals;
use App\Data\ProposalFilters;
use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Tag;

it('combines search, tag and status filters for the viewer, newest first', function (): void {
    $tag = Tag::factory()->named('Laravel')->create();
    $match = Proposal::factory()->approved()->withTags([$tag])->create(['title' => 'Laravel at scale', 'created_at' => now()->subDay()]);
    $newer = Proposal::factory()->approved()->withTags([$tag])->create(['title' => 'Laravel queues']);
    Proposal::factory()->withTags([$tag])->create(['title' => 'Laravel pending']);
    Proposal::factory()->approved()->create(['title' => 'Laravel untagged']);

    $page = app(ListProposals::class)->handle(
        userWithRole(Role::Reviewer),
        new ProposalFilters(search: 'laravel', tags: [$tag->normalized_name], status: ProposalStatus::Approved, perPage: 10),
    );

    expect(collect($page->items())->pluck('id')->all())->toBe([$newer->id, $match->id])
        ->and($page->items()[0]->relationLoaded('author'))->toBeTrue();
});
