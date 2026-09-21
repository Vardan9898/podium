<?php

declare(strict_types=1);

namespace App\Actions\Notifications;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

final class ListNotifications
{
    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function handle(User $user): LengthAwarePaginator
    {
        return $user->notifications()->paginate(config()->integer('proposals.pagination.notifications_per_page'));
    }
}
