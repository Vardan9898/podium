<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;

it('shows speakers only their own proposals', function (): void {
    $speaker = userWithRole(Role::Speaker);
    $own = Proposal::factory()->for($speaker, 'author')->create();
    Proposal::factory()->count(2)->create();

    $this->actingAs($speaker)->getJson('/api/proposals')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $own->id)
        ->assertJsonMissingPath('data.0.average_rating');
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

it('filters by any of several tags, matched by name case-insensitively', function (): void {
    $php = Tag::factory()->named('PHP')->create();
    $vue = Tag::factory()->named('Vue.js')->create();
    $go = Tag::factory()->named('Go')->create();
    $a = Proposal::factory()->withTags([$php])->create();
    $b = Proposal::factory()->withTags([$vue, $php])->create();
    Proposal::factory()->withTags([$go])->create();
    Proposal::factory()->create();

    $response = $this->actingAs(userWithRole(Role::Reviewer))
        ->getJson('/api/proposals?'.http_build_query(['tags' => ['php', 'VUE.JS']]))
        ->assertOk();

    expect($response->json('data.*.id'))->toEqualCanonicalizing([$a->id, $b->id]);
});

it('treats a search for "0" as a real term, not an empty filter', function (): void {
    Proposal::factory()->create(['title' => 'From 0 to 60 with Laravel']);
    Proposal::factory()->count(2)->create(['title' => 'Something else']);

    $this->actingAs(userWithRole(Role::Reviewer))->getJson('/api/proposals?search=0')
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.title', 'From 0 to 60 with Laravel');
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

it('includes review stats and costs the same number of queries whatever the page holds', function (): void {
    $proposal = Proposal::factory()->withTags(2)->create(['title' => 'Stats subject']);
    Review::factory()->for($proposal)->create(['rating' => 4]);
    Review::factory()->for($proposal)->create(['rating' => 7]);
    $reviewer = userWithRole(Role::Reviewer);

    $countQueries = function () use ($reviewer): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($reviewer)->getJson('/api/proposals')->assertOk();

        return count(DB::getQueryLog());
    };

    $countQueries();          // warm-up: the first request also loads and caches permissions
    $withOne = $countQueries();

    Proposal::factory()->count(19)->withTags(2)->hasReviews(2)->create();
    $withTwenty = $countQueries();

    // Constant, not proportional: eager loading + aggregates, no per-proposal query.
    expect($withTwenty)->toBe($withOne)->toBeLessThan(8);

    $this->actingAs($reviewer)->getJson('/api/proposals?search=Stats+subject')
        ->assertJsonPath('data.0.reviews_count', 2)
        ->assertJsonPath('data.0.average_rating', 5.5)
        ->assertJsonMissingPath('data.0.reviews');
});

it('refuses absurd page numbers instead of scanning for them', function (): void {
    $admin = userWithRole(Role::Admin);

    $this->actingAs($admin)->getJson('/api/proposals?page='.(config()->integer('proposals.pagination.max_page') + 1))
        ->assertJsonValidationErrors('page');
    $this->actingAs($admin)->getJson('/api/proposals?page=0')->assertJsonValidationErrors('page');
    $this->actingAs($admin)->getJson('/api/proposals?page=2')->assertOk();
});
