<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Data\ProposalActivity;
use App\Enums\Permission;
use App\Enums\ProposalActivityType;
use App\Events\ProposalReviewed;
use App\Models\User;
use App\Notifications\ProposalActivityNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

final class NotifyAboutReviewedProposal
{
    public function handle(ProposalReviewed $event): void
    {
        $recipients = User::query()
            ->where(fn (Builder $query) => $query
                ->whereKey($event->proposal->user_id)
                ->orWhere(fn (Builder $admins) => $admins->permission(Permission::ChangeProposalStatus)))
            ->whereKeyNot($event->actor->id)
            ->get();

        $verb = $event->review->wasRecentlyCreated ? 'reviewed' : 'updated their review of';

        Notification::send($recipients, new ProposalActivityNotification(ProposalActivity::for(
            ProposalActivityType::Reviewed,
            $event->proposal,
            $event->actor,
            "{$event->actor->name} {$verb} a proposal.",
        )));
    }
}
