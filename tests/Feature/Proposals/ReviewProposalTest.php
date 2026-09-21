<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Events\ProposalReviewed;
use App\Models\Proposal;
use App\Models\Review;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->reviewer = userWithRole(Role::Reviewer);
    $this->proposal = Proposal::factory()->create();
});

it('creates a review, then updates the same one on resubmit', function (): void {
    Event::fake([ProposalReviewed::class]);
    $url = "/api/proposals/{$this->proposal->id}/review";

    $first = $this->actingAs($this->reviewer)->putJson($url, ['rating' => 6, 'comment' => 'Solid.'])
        ->assertCreated()
        ->assertJsonPath('data.rating', 6)
        ->assertJsonPath('data.reviewer.id', $this->reviewer->id);

    $this->actingAs($this->reviewer)->putJson($url, ['rating' => 9, 'comment' => 'Even better after rereading.'])
        ->assertOk()
        ->assertJsonPath('data.id', $first->json('data.id'))
        ->assertJsonPath('data.rating', 9);

    expect(Review::query()->count())->toBe(1);
    Event::assertDispatchedTimes(ProposalReviewed::class, 2);
});

it('keeps one review per reviewer while allowing many reviewers', function (): void {
    Review::factory()->for($this->proposal)->create();

    $this->actingAs($this->reviewer)
        ->putJson("/api/proposals/{$this->proposal->id}/review", ['rating' => 5, 'comment' => 'Ok'])
        ->assertCreated();

    expect($this->proposal->reviews()->count())->toBe(2);
});

it('enforces rating bounds from config', function (): void {
    config(['proposals.rating.min' => 1, 'proposals.rating.max' => 5]);
    $url = "/api/proposals/{$this->proposal->id}/review";

    $this->actingAs($this->reviewer)->putJson($url, ['rating' => 6, 'comment' => 'x'])->assertJsonValidationErrors('rating');
    $this->actingAs($this->reviewer)->putJson($url, ['rating' => 0, 'comment' => 'x'])->assertJsonValidationErrors('rating');
    $this->actingAs($this->reviewer)->putJson($url, ['rating' => 5, 'comment' => 'x'])->assertCreated();
});

it('validates the review payload', function (array $payload, string $field): void {
    $this->actingAs($this->reviewer)
        ->putJson("/api/proposals/{$this->proposal->id}/review", $payload)
        ->assertJsonValidationErrors($field);
})->with([
    'missing rating' => [['comment' => 'x'], 'rating'],
    'non-integer rating' => [['rating' => 7.5, 'comment' => 'x'], 'rating'],
    'missing comment' => [['rating' => 5], 'comment'],
    'comment too long' => [['rating' => 5, 'comment' => str_repeat('a', 2001)], 'comment'],
]);

it('forbids users without the review permission', function (Role $role): void {
    $this->actingAs(userWithRole($role))
        ->putJson("/api/proposals/{$this->proposal->id}/review", ['rating' => 5, 'comment' => 'x'])
        ->assertForbidden();
})->with([Role::Speaker, Role::Admin]);
