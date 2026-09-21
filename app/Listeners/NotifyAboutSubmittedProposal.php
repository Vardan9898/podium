<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Data\ProposalActivity;
use App\Enums\Permission;
use App\Enums\ProposalActivityType;
use App\Events\ProposalSubmitted;
use App\Models\User;
use App\Notifications\ProposalActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyAboutSubmittedProposal implements ShouldQueue
{
    public function handle(ProposalSubmitted $event): void
    {
        $recipients = User::permission(Permission::ViewAnyProposals)
            ->whereKeyNot($event->actor->id)
            ->get();

        Notification::send($recipients, new ProposalActivityNotification(ProposalActivity::for(
            ProposalActivityType::Submitted,
            $event->proposal,
            $event->actor,
            "{$event->actor->name} submitted a new proposal.",
        )));
    }
}
