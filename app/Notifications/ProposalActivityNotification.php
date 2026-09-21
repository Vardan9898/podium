<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Data\ProposalActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification, two channels: "database" backs the bell/history,
 * "broadcast" pushes the same payload live over Reverb.
 */
final class ProposalActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ProposalActivity $activity,
    ) {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return $this->activity->toArray();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->activity->toArray());
    }

    public function databaseType(object $notifiable): string
    {
        return $this->activity->type->value;
    }

    public function broadcastType(): string
    {
        return $this->activity->type->value;
    }
}
