<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;

final class MarkNotificationsRead
{
    /**
     * @param  list<string>|null  $ids  Null marks every unread notification as read.
     */
    public function handle(User $user, ?array $ids): void
    {
        $user->unreadNotifications()
            ->when($ids !== null, fn ($query) => $query->whereIn('id', $ids ?? []))
            ->update(['read_at' => now()]);
    }
}
