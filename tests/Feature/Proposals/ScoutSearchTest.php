<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Tag;

/*
 * Title search goes through Laravel Scout. The "collection" engine filters in PHP, like a
 * hosted engine (Meilisearch) would outside the database, so it proves the list pipeline
 * does not depend on the database engine.
 */
dataset('engines', ['database', 'collection']);

it('finds proposals by title with any Scout engine', function (string $engine): void {
    config(['scout.driver' => $engine]);
    Proposal::factory()->create(['title' => 'Mastering Laravel Queues']);
    Proposal::factory()->create(['title' => 'Vue for beginners']);

    $this->actingAs(userWithRole(Role::Reviewer))->getJson('/api/proposals?search=QUEUES')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Mastering Laravel Queues');
})->with('engines');

it('keeps visibility, filters and pagination counts correct with any engine', function (string $engine): void {
    config(['scout.driver' => $engine]);
    $speaker = userWithRole(Role::Speaker);
    $tag = Tag::factory()->named('Laravel')->create();
    Proposal::factory()->count(3)->for($speaker, 'author')->approved()->withTags([$tag])->create(['title' => 'Laravel tips']);
    Proposal::factory()->for($speaker, 'author')->withTags([$tag])->create(['title' => 'Laravel pending']);
    Proposal::factory()->approved()->withTags([$tag])->create(['title' => 'Laravel by someone else']);

    $this->actingAs($speaker)
        ->getJson('/api/proposals?'.http_build_query([
            'search' => 'laravel',
            'tags' => ['Laravel'],
            'status' => ProposalStatus::Approved->value,
            'per_page' => 2,
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('meta.last_page', 2);
})->with('engines');

it('matches LIKE wildcards literally on the database engine', function (): void {
    config(['scout.driver' => 'database']);
    Proposal::factory()->create(['title' => '100% uptime']);
    Proposal::factory()->create(['title' => 'snake_case everywhere']);
    Proposal::factory()->create(['title' => 'Plain title']);

    $reviewer = userWithRole(Role::Reviewer);

    $this->actingAs($reviewer)->getJson('/api/proposals?search=%25')->assertJsonCount(1, 'data');
    $this->actingAs($reviewer)->getJson('/api/proposals?search=_')->assertJsonCount(1, 'data');
});

it('indexes only the title', function (): void {
    $proposal = Proposal::factory()->make(['title' => 'Edge caching', 'description' => 'Private notes']);

    expect($proposal->toSearchableArray())->toBe(['title' => 'Edge caching']);
});

it('never truncates or hides results on the default engine, whatever the cap says', function (): void {
    config(['scout.driver' => 'database', 'proposals.search.max_matches' => 2]);
    $speaker = userWithRole(Role::Speaker);
    Proposal::factory()->count(5)->create(['title' => 'Laravel everywhere']);
    $own = Proposal::factory()->for($speaker, 'author')->create(['title' => 'Laravel for beginners']);

    $this->actingAs(userWithRole(Role::Reviewer))->getJson('/api/proposals?search=laravel')
        ->assertJsonPath('meta.total', 6);

    // The speaker's own match must never fall outside a cap spent on proposals they cannot see.
    $this->actingAs($speaker)->getJson('/api/proposals?search=laravel')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $own->id);
});
