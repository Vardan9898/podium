<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Data\NotificationSelection;
use App\Models\User;

final class MarkNotificationsRead
{
    public function handle(User $user, NotificationSelection $selection): void
    {
        $user->unreadNotifications()
            ->when($selection->ids !== null, fn ($query) => $query->whereIn('id', $selection->ids ?? []))
            ->update(['read_at' => now()]);
    }
}
