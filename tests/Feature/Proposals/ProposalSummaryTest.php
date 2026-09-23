<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Review;

it('counts only what the viewer may see', function (): void {
    $speaker = userWithRole(Role::Speaker);
    Proposal::factory()->count(2)->for($speaker, 'author')->create();
    Proposal::factory()->for($speaker, 'author')->approved()->create();
    Proposal::factory()->count(4)->rejected()->create();

    $this->actingAs($speaker)->getJson('/api/proposals/summary')
        ->assertOk()
        ->assertJsonPath('data.total', 3)
        ->assertJsonPath('data.by_status', [
            ProposalStatus::Pending->value => 2,
            ProposalStatus::Approved->value => 1,
            ProposalStatus::Rejected->value => 0,
        ])
        ->assertJsonPath('data.awaiting_my_review', null);

    $this->actingAs(userWithRole(Role::Admin))->getJson('/api/proposals/summary')
        ->assertJsonPath('data.total', 7)
        ->assertJsonPath('data.by_status.'.ProposalStatus::Rejected->value, 4);
});

it('tells a reviewer how many proposals still need them', function (): void {
    $reviewer = userWithRole(Role::Reviewer);
    $reviewed = Proposal::factory()->create();
    Review::factory()->for($reviewed)->for($reviewer, 'author')->create();
    Proposal::factory()->count(3)->create();
    // Someone else's review must not count as this reviewer's.
    Review::factory()->for(Proposal::query()->latest('id')->firstOrFail())->create();

    $this->actingAs($reviewer)->getJson('/api/proposals/summary')
        ->assertJsonPath('data.total', 4)
        ->assertJsonPath('data.awaiting_my_review', 3);
});

it('filters the list down to the reviewer\'s own queue', function (): void {
    $reviewer = userWithRole(Role::Reviewer);
    $done = Proposal::factory()->create(['title' => 'Already reviewed']);
    Review::factory()->for($done)->for($reviewer, 'author')->create();
    $todo = Proposal::factory()->create(['title' => 'Still waiting']);

    $this->actingAs($reviewer)->getJson('/api/proposals?awaiting_review=1')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $todo->id);

    $this->actingAs($reviewer)->getJson('/api/proposals')->assertJsonPath('meta.total', 2);
});

it('ignores the queue filter for users who do not review', function (): void {
    $speaker = userWithRole(Role::Speaker);
    $reviewed = Proposal::factory()->for($speaker, 'author')->create();
    Proposal::factory()->for($speaker, 'author')->create();

    // The speaker authored this review while they still had the reviewer role. Without the
    // permission guard the filter would silently hide that proposal from its own author.
    Review::factory()->for($reviewed)->for($speaker, 'author')->create();

    $this->actingAs($speaker)->getJson('/api/proposals?awaiting_review=1')->assertJsonPath('meta.total', 2);

    $admin = userWithRole(Role::Admin);
    $this->actingAs($admin)->getJson('/api/proposals?awaiting_review=1')->assertJsonPath('meta.total', 2);
    $this->actingAs($admin)->getJson('/api/proposals/summary')->assertJsonPath('data.awaiting_my_review', null);
});

it('accepts every reasonable spelling of the queue flag, and rejects nonsense', function (string $value, int $expected): void {
    $reviewer = userWithRole(Role::Reviewer);
    $reviewed = Proposal::factory()->create();
    Review::factory()->for($reviewed)->for($reviewer, 'author')->create();
    Proposal::factory()->create();

    $this->actingAs($reviewer)->getJson("/api/proposals?awaiting_review={$value}")
        ->assertOk()
        ->assertJsonPath('meta.total', $expected);
})->with([
    'query-string true' => ['true', 1],
    'numeric true' => ['1', 1],
    'query-string false' => ['false', 2],
    'numeric false' => ['0', 2],
]);

it('rejects a non-boolean queue filter', function (): void {
    $this->actingAs(userWithRole(Role::Reviewer))->getJson('/api/proposals?awaiting_review=maybe')
        ->assertJsonValidationErrors('awaiting_review');
});

it('is reachable as a path, not mistaken for a proposal id', function (): void {
    $this->actingAs(userWithRole(Role::Admin))->getJson('/api/proposals/summary')->assertOk();
    $this->actingAs(userWithRole(Role::Admin))->getJson('/api/proposals/not-a-number')->assertNotFound();
});
