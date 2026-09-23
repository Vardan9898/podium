<?php

declare(strict_types=1);

use App\Events\ProposalReviewed;
use App\Events\ProposalStatusChanged;
use App\Events\ProposalSubmitted;
use App\Listeners\NotifyAboutProposalStatusChange;
use App\Listeners\NotifyAboutReviewedProposal;
use App\Listeners\NotifyAboutSubmittedProposal;
use App\Notifications\ProposalActivityNotification;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/*
 * Notifications must never be able to undo the write that caused them: events wait for the
 * commit, listeners and notifications go to the queue. The suite runs the queue synchronously,
 * so this is asserted structurally — removing any of these contracts fails here.
 */
it('dispatches domain events only after the transaction commits', function (string $event): void {
    expect(is_a($event, ShouldDispatchAfterCommit::class, true))->toBeTrue();
})->with([ProposalSubmitted::class, ProposalReviewed::class, ProposalStatusChanged::class]);

it('keeps recipient fan-out on the queue', function (string $listener): void {
    expect(is_a($listener, ShouldQueue::class, true))->toBeTrue();
})->with([NotifyAboutSubmittedProposal::class, NotifyAboutReviewedProposal::class, NotifyAboutProposalStatusChange::class]);

it('queues the notification itself, after commit', function (): void {
    $notification = app(ProposalActivityNotification::class, ['activity' => new App\Data\ProposalActivity(
        App\Enums\ProposalActivityType::Submitted, 1, 'A talk', 'New proposal.', 'Sam',
    )]);

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($notification->afterCommit)->toBeTrue();
});
