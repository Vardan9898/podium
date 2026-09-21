<?php

declare(strict_types=1);

use App\Actions\Proposals\ChangeProposalStatus;
use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Events\ProposalStatusChanged;
use App\Models\Proposal;
use Illuminate\Support\Facades\Event;

it('updates the status and dispatches an event with the previous value', function (): void {
    Event::fake([ProposalStatusChanged::class]);
    $proposal = Proposal::factory()->create();

    app(ChangeProposalStatus::class)->handle(userWithRole(Role::Admin), $proposal, ProposalStatus::Rejected);

    expect($proposal->refresh()->status)->toBe(ProposalStatus::Rejected);
    Event::assertDispatched(ProposalStatusChanged::class, fn (ProposalStatusChanged $e) => $e->previous === ProposalStatus::Pending);
});

it('is a no-op for the current status', function (): void {
    Event::fake([ProposalStatusChanged::class]);
    $proposal = Proposal::factory()->rejected()->create();

    app(ChangeProposalStatus::class)->handle(userWithRole(Role::Admin), $proposal, ProposalStatus::Rejected);

    expect($proposal->isDirty())->toBeFalse();
    Event::assertNotDispatched(ProposalStatusChanged::class);
});
