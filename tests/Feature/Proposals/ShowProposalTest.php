<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('lets speakers open their own proposal but not anyone else\'s', function (): void {
    $speaker = userWithRole(Role::Speaker);
    $own = Proposal::factory()->for($speaker, 'author')->create();
    $other = Proposal::factory()->create();

    $this->actingAs($speaker)->getJson("/api/proposals/{$own->id}")->assertOk()->assertJsonMissingPath('data.reviews');
    $this->actingAs($speaker)->getJson("/api/proposals/{$other->id}")->assertNotFound()->assertExactJson(['message' => 'Not found.']);
});

it('includes reviews and scores only for users allowed to read them', function (Role $role, bool $seesReviews): void {
    $proposal = Proposal::factory()->create();
    Review::factory()->count(2)->for($proposal)->create();
    $viewer = $role === Role::Speaker ? User::query()->findOrFail($proposal->user_id) : userWithRole($role);

    $response = $this->actingAs($viewer)->getJson("/api/proposals/{$proposal->id}")->assertOk();

    $seesReviews
        ? $response->assertJsonCount(2, 'data.reviews')
            ->assertJsonPath('data.reviews_count', 2)
            ->assertJsonStructure(['data' => ['average_rating', 'reviews' => [['rating', 'comment', 'reviewer' => ['id', 'name']]]]])
        : $response->assertJsonMissingPath('data.reviews')
            ->assertJsonMissingPath('data.reviews_count')
            ->assertJsonMissingPath('data.average_rating');
})->with([
    'speaker (author)' => [Role::Speaker, false],
    'reviewer' => [Role::Reviewer, true],
    'admin' => [Role::Admin, true],
]);

it('never exposes author email addresses', function (): void {
    $proposal = Proposal::factory()->create();

    $this->actingAs(userWithRole(Role::Reviewer))->getJson("/api/proposals/{$proposal->id}")
        ->assertJsonMissingPath('data.author.email');
});

it('returns a generic JSON 404 for unknown proposals', function (): void {
    $this->actingAs(userWithRole(Role::Admin))->getJson('/api/proposals/999999')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);
});

describe('attachment download', function (): void {
    beforeEach(function (): void {
        Storage::fake('local');
        Storage::disk('local')->put('proposals/1/abc.pdf', '%PDF-1.4 test');
    });

    it('streams the file to authorised users under its original name', function (Role $role): void {
        $proposal = Proposal::factory()->withAttachment('proposals/1/abc.pdf', 'my talk.pdf')->create();

        $response = $this->actingAs(userWithRole($role))->get("/api/proposals/{$proposal->id}/attachment")->assertOk();

        expect($response->headers->get('content-disposition'))->toContain('my talk.pdf')
            ->and($response->streamedContent())->toBe('%PDF-1.4 test');
    })->with([Role::Reviewer, Role::Admin]);

    it('lets the author download and blocks other speakers', function (): void {
        $author = userWithRole(Role::Speaker);
        $proposal = Proposal::factory()->for($author, 'author')->withAttachment('proposals/1/abc.pdf')->create();

        $this->actingAs($author)->get("/api/proposals/{$proposal->id}/attachment")->assertOk();
        $this->actingAs(userWithRole(Role::Speaker))->getJson("/api/proposals/{$proposal->id}/attachment")->assertNotFound();
    });

    it('returns 404 when the file is missing from disk', function (): void {
        $proposal = Proposal::factory()->withAttachment('proposals/1/gone.pdf')->create();

        $this->actingAs(userWithRole(Role::Admin))->getJson("/api/proposals/{$proposal->id}/attachment")->assertNotFound();
    });

    it('returns 404 when the proposal has no attachment', function (): void {
        $proposal = Proposal::factory()->create();

        $this->actingAs(userWithRole(Role::Admin))->getJson("/api/proposals/{$proposal->id}/attachment")->assertNotFound();
    });
});
