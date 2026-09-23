<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Data\ProposalActivity;
use App\Enums\ProposalActivityType;
use App\Events\ProposalStatusChanged;
use App\Models\User;
use App\Notifications\ProposalActivityNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;

final class NotifyAboutProposalStatusChange implements ShouldQueue
{
    public function handle(ProposalStatusChanged $event): void
    {
        $proposal = $event->proposal;

        $recipients = User::query()
            ->where(fn (Builder $query) => $query
                ->whereKey($proposal->user_id)
                ->orWhereHas('reviews', fn (Builder $reviews) => $reviews->whereBelongsTo($proposal)))
            ->whereKeyNot($event->actor->id)
            ->get();

        Notification::send($recipients, new ProposalActivityNotification(ProposalActivity::for(
            ProposalActivityType::StatusChanged,
            $proposal,
            $event->actor,
            "Status changed from {$event->previous->value} to {$event->current->value}.",
        )));
    }
}
