<?php

declare(strict_types=1);

use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Events\ProposalStatusChanged;
use App\Models\Proposal;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([ProposalStatusChanged::class]);
    $this->admin = userWithRole(Role::Admin);
});

it('changes the status and dispatches an event', function (): void {
    $proposal = Proposal::factory()->create();

    $this->actingAs($this->admin)
        ->patchJson("/api/proposals/{$proposal->id}/status", ['status' => ProposalStatus::Approved->value])
        ->assertOk()
        ->assertJsonPath('data.status', ProposalStatus::Approved->value);

    expect($proposal->refresh()->status)->toBe(ProposalStatus::Approved);
    Event::assertDispatched(ProposalStatusChanged::class, fn (ProposalStatusChanged $e) => $e->previous === ProposalStatus::Pending
        && $e->proposal->is($proposal)
        && $e->actor->is($this->admin));
});

it('does nothing and dispatches no event when the status is unchanged', function (): void {
    $proposal = Proposal::factory()->approved()->create();
    $updatedAt = $proposal->updated_at;

    $this->actingAs($this->admin)
        ->patchJson("/api/proposals/{$proposal->id}/status", ['status' => ProposalStatus::Approved->value])
        ->assertOk();

    expect($proposal->refresh()->updated_at->equalTo($updatedAt))->toBeTrue();
    Event::assertNotDispatched(ProposalStatusChanged::class);
});

it('rejects unknown statuses', function (): void {
    $proposal = Proposal::factory()->create();

    $this->actingAs($this->admin)
        ->patchJson("/api/proposals/{$proposal->id}/status", ['status' => 'archived'])
        ->assertJsonValidationErrors('status');
});

it('forbids reviewers, who may see the proposal but not decide', function (): void {
    $proposal = Proposal::factory()->create();

    $this->actingAs(userWithRole(Role::Reviewer))
        ->patchJson("/api/proposals/{$proposal->id}/status", ['status' => ProposalStatus::Approved->value])
        ->assertForbidden();
});

it('hides other speakers\' proposals behind the same 404 as a missing one', function (): void {
    $proposal = Proposal::factory()->create();
    $speaker = userWithRole(Role::Speaker);
    $payload = ['status' => ProposalStatus::Approved->value];

    $foreign = $this->actingAs($speaker)->patchJson("/api/proposals/{$proposal->id}/status", $payload)->assertNotFound();
    $missing = $this->actingAs($speaker)->patchJson('/api/proposals/999999/status', $payload)->assertNotFound();

    expect($foreign->json())->toBe($missing->json())->toBe(['message' => 'Not found.']);
});
