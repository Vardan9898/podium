<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Data\ProposalActivity;
use App\Enums\Permission;
use App\Enums\ProposalActivityType;
use App\Events\ProposalReviewed;
use App\Models\User;
use App\Notifications\ProposalActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyAboutReviewedProposal implements ShouldQueue
{
    public function handle(ProposalReviewed $event): void
    {
        $proposal = $event->proposal;
        $actor = $event->actor;
        $verb = $event->isNewReview ? 'reviewed' : 'updated their review of';

        $admins = User::permission(Permission::ChangeProposalStatus)
            ->whereKeyNot([$actor->id, $proposal->user_id])
            ->get();

        Notification::send($admins, new ProposalActivityNotification(ProposalActivity::for(
            ProposalActivityType::Reviewed,
            $proposal,
            $actor,
            "{$actor->name} {$verb} a proposal.",
        )));

        // Authors can't read reviews, so they learn that one arrived, not who wrote it or what it says.
        if ($event->isNewReview && $proposal->user_id !== $actor->id) {
            Notification::send(User::query()->whereKey($proposal->user_id)->get(), new ProposalActivityNotification(ProposalActivity::for(
                ProposalActivityType::Reviewed,
                $proposal,
                'A reviewer',
                'Your proposal received a new review.',
            )));
        }
    }
}
