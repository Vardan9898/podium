<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Tag;

it('shows speakers only their own proposals', function (): void {
    $speaker = userWithRole(Role::Speaker);
    $own = Proposal::factory()->for($speaker, 'author')->create();
    Proposal::factory()->count(2)->create();

    $this->actingAs($speaker)->getJson('/api/proposals')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->id);
});

it('shows reviewers and admins every proposal', function (Role $role): void {
    Proposal::factory()->count(3)->create();

    $this->actingAs(userWithRole($role))->getJson('/api/proposals')
        ->assertOk()
        ->assertJsonCount(3, 'data');
})->with([Role::Reviewer, Role::Admin]);

it('searches by title, case-insensitively and treating wildcards literally', function (): void {
    Proposal::factory()->create(['title' => 'Mastering Laravel Queues']);
    Proposal::factory()->create(['title' => 'Vue for beginners']);
    Proposal::factory()->create(['title' => '100% uptime']);

    $reviewer = userWithRole(Role::Reviewer);

    $this->actingAs($reviewer)->getJson('/api/proposals?search=laravel')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Mastering Laravel Queues');

    $this->actingAs($reviewer)->getJson('/api/proposals?search=%25')
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', '100% uptime');
});

it('filters by any of several tags', function (): void {
    [$php, $vue, $go] = Tag::factory()->count(3)->create()->all();
    $a = Proposal::factory()->withTags([$php])->create();
    $b = Proposal::factory()->withTags([$vue, $php])->create();
    Proposal::factory()->withTags([$go])->create();
    Proposal::factory()->create();

    $response = $this->actingAs(userWithRole(Role::Reviewer))
        ->getJson('/api/proposals?'.http_build_query(['tags' => [$php->slug, $vue->slug]]))
        ->assertOk();

    expect($response->json('data.*.id'))->toEqualCanonicalizing([$a->id, $b->id]);
});

it('filters by status', function (): void {
    Proposal::factory()->approved()->count(2)->create();
    Proposal::factory()->rejected()->create();
    Proposal::factory()->create();

    $this->actingAs(userWithRole(Role::Admin))
        ->getJson('/api/proposals?status='.ProposalStatus::Approved->value)
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.status', ProposalStatus::Approved->value);
});

it('paginates within bounds', function (): void {
    Proposal::factory()->count(12)->create();
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->getJson('/api/proposals?per_page=5&page=3')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 12)
        ->assertJsonPath('meta.last_page', 3);

    $this->actingAs($admin)->getJson('/api/proposals?per_page=51')->assertJsonValidationErrors('per_page');
    $this->actingAs($admin)->getJson('/api/proposals?per_page=0')->assertJsonValidationErrors('per_page');
    $this->actingAs($admin)->getJson('/api/proposals?status=archived')->assertJsonValidationErrors('status');
});

it('includes review stats without N+1 queries', function (): void {
    $proposal = Proposal::factory()->withTags(2)->create();
    Review::factory()->for($proposal)->create(['rating' => 4]);
    Review::factory()->for($proposal)->create(['rating' => 7]);
    Proposal::factory()->count(5)->withTags(2)->create();

    // Strict mode (Model::shouldBeStrict) turns any lazy load into an exception.
    $this->actingAs(userWithRole(Role::Reviewer))->getJson('/api/proposals')
        ->assertOk()
        ->assertJsonPath('data.5.reviews_count', 2)
        ->assertJsonPath('data.5.average_rating', 5.5)
        ->assertJsonMissingPath('data.0.reviews');
});
