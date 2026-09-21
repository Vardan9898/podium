<?php

declare(strict_types=1);

use App\Actions\Proposals\ChangeProposalStatus;
use App\Actions\Proposals\ReviewProposal;
use App\Actions\Proposals\SubmitProposal;
use App\Data\ProposalData;
use App\Data\ReviewData;
use App\Enums\ProposalActivityType;
use App\Enums\ProposalStatus;
use App\Enums\Role;
use App\Models\Proposal;
use App\Models\Review;
use App\Notifications\ProposalActivityNotification;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->speaker = userWithRole(Role::Speaker);
    $this->otherSpeaker = userWithRole(Role::Speaker);
    $this->reviewer = userWithRole(Role::Reviewer);
    $this->otherReviewer = userWithRole(Role::Reviewer);
    $this->admin = userWithRole(Role::Admin);
});

it('notifies everyone who can see all proposals when one is submitted', function (): void {
    $proposal = app(SubmitProposal::class)->handle($this->speaker, new ProposalData('Talk', 'Body'));

    Notification::assertSentTo(
        [$this->reviewer, $this->otherReviewer, $this->admin],
        ProposalActivityNotification::class,
        fn (ProposalActivityNotification $n) => $n->activity->type === ProposalActivityType::Submitted
            && $n->activity->proposalId === $proposal->id
            && $n->activity->actorName === $this->speaker->name,
    );
    Notification::assertNotSentTo([$this->speaker, $this->otherSpeaker], ProposalActivityNotification::class);
});

it('notifies the author and admins when a proposal is reviewed, never the reviewer', function (): void {
    $proposal = Proposal::factory()->for($this->speaker, 'author')->create();

    app(ReviewProposal::class)->handle($this->reviewer, $proposal, new ReviewData(8, 'Great'));

    Notification::assertSentTo(
        [$this->speaker, $this->admin],
        ProposalActivityNotification::class,
        fn (ProposalActivityNotification $n) => $n->activity->type === ProposalActivityType::Reviewed,
    );
    Notification::assertNotSentTo(
        [$this->reviewer, $this->otherReviewer, $this->otherSpeaker],
        ProposalActivityNotification::class,
    );
});

it('tells the author a review arrived without revealing the reviewer', function (): void {
    $proposal = Proposal::factory()->for($this->speaker, 'author')->create();

    app(ReviewProposal::class)->handle($this->reviewer, $proposal, new ReviewData(8, 'Great'));

    Notification::assertSentTo($this->speaker, ProposalActivityNotification::class, fn (ProposalActivityNotification $n) => $n->activity->actorName === 'A reviewer'
        && ! str_contains($n->activity->message, $this->reviewer->name));
    Notification::assertSentTo($this->admin, ProposalActivityNotification::class, fn (ProposalActivityNotification $n) => $n->activity->actorName === $this->reviewer->name);
});

it('notifies admins but not the author when a review is edited, and nobody when nothing changed', function (): void {
    $proposal = Proposal::factory()->for($this->speaker, 'author')->create();
    $action = app(ReviewProposal::class);
    $action->handle($this->reviewer, $proposal, new ReviewData(8, 'Great'));
    Notification::fake();

    $action->handle($this->reviewer, $proposal, new ReviewData(9, 'Even better'));
    Notification::assertSentTo($this->admin, ProposalActivityNotification::class);
    Notification::assertNotSentTo($this->speaker, ProposalActivityNotification::class);

    Notification::fake();
    $action->handle($this->reviewer, $proposal, new ReviewData(9, 'Even better'));
    Notification::assertNothingSent();
});

it('notifies the author and the reviewers of that proposal on a status change, never the admin', function (): void {
    $proposal = Proposal::factory()->for($this->speaker, 'author')->create();
    Review::factory()->for($proposal)->for($this->reviewer, 'author')->create();

    app(ChangeProposalStatus::class)->handle($this->admin, $proposal, ProposalStatus::Rejected);

    Notification::assertSentTo(
        [$this->speaker, $this->reviewer],
        ProposalActivityNotification::class,
        fn (ProposalActivityNotification $n) => $n->activity->type === ProposalActivityType::StatusChanged,
    );
    Notification::assertNotSentTo(
        [$this->admin, $this->otherReviewer, $this->otherSpeaker],
        ProposalActivityNotification::class,
    );
});

it('sends nothing when the status does not actually change', function (): void {
    $proposal = Proposal::factory()->approved()->for($this->speaker, 'author')->create();

    app(ChangeProposalStatus::class)->handle($this->admin, $proposal, ProposalStatus::Approved);

    Notification::assertNothingSent();
});

it('stores and broadcasts a minimal payload on the user\'s private channel', function (): void {
    $proposal = Proposal::factory()->for($this->speaker, 'author')->create(['title' => 'Edge caching']);
    app(ReviewProposal::class)->handle($this->reviewer, $proposal, new ReviewData(8, 'Secret reviewer comment'));

    Notification::assertSentTo($this->speaker, function (ProposalActivityNotification $n, array $channels) use ($proposal) {
        $payload = $n->toArray($this->speaker);

        expect($channels)->toBe(['database', 'broadcast'])
            ->and((new BroadcastNotificationCreated($this->speaker, $n, $payload))->broadcastOn()[0]->name)
            ->toBe("private-App.Models.User.{$this->speaker->id}")
            ->and(array_keys($payload))->toBe(['type', 'proposal_id', 'proposal_title', 'message', 'actor_name'])
            ->and($payload['proposal_id'])->toBe($proposal->id)
            ->and(json_encode($payload))->not->toContain('Secret reviewer comment');

        return true;
    });
});
